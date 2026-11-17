<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Material;
use App\Models\Movimiento;
use App\Models\Proveedor;
use App\Services\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompraController extends Controller
{
    public function index(Request $r, Inventario $inv)
    {
        $sugeridos = Material::orderBy('nombre')->get()->map(function ($m) use ($inv) {
            $m->pendiente = $inv->pendiente($m->id_material);
            $m->sugerido = $inv->sugerido($m);
            return $m;
        })->filter(fn ($m) => $m->bajo || $m->pendiente > $m->stock_actual);
        return view('compras', [
            'sugeridos' => $sugeridos,
            'proveedores' => Proveedor::orderBy('razon_social')->get(),
            'materiales' => Material::orderBy('nombre')->get(),
            'pre' => Material::find($r->query('material')), 'preCantidad' => $r->query('cantidad'),
            'entradas' => Movimiento::with(['material', 'usuario'])->where('tipo', 'ENTRADA')->latest('fecha')->limit(10)->get(),
        ]);
    }

    /** Compra recibida: lo rechazado por falla no entra al stock */
    public function store(Request $r, Inventario $inv)
    {
        $this->permitir('compra');
        $d = $r->validate([
            'id_proveedor' => 'required|exists:proveedores,id_proveedor',
            'id_material' => 'required|exists:materiales,id_material',
            'cantidad' => 'required|numeric|min:0.01',
            'cantidad_rechazada' => 'nullable|numeric|min:0',
            'precio_unitario' => 'required|numeric|min:0',
        ], ['cantidad.min' => 'La cantidad recibida debe ser mayor a 0.']);
        $rech = (float) ($d['cantidad_rechazada'] ?? 0);
        if ($rech > $d['cantidad']) throw ValidationException::withMessages(['cantidad_rechazada' => 'Lo rechazado no puede ser mayor a lo recibido.']);
        $aceptada = round($d['cantidad'] - $rech, 2);
        $m = Material::find($d['id_material']);
        $p = Proveedor::find($d['id_proveedor']);

        DB::transaction(function () use ($d, $rech, $aceptada, $m, $p, $inv) {
            $c = Compra::create(['id_proveedor' => $p->id_proveedor, 'id_usuario' => Auth::id(), 'fecha' => today(),
                'total' => round($aceptada * $d['precio_unitario'], 2)]);
            DetalleCompra::create(['id_compra' => $c->id_compra, 'id_material' => $m->id_material, 'cantidad' => $d['cantidad'],
                'cantidad_rechazada' => $rech, 'precio_unitario' => $d['precio_unitario']]);
            if ($aceptada > 0) {
                // costo promedio ponderado
                $stock = $m->stock_actual;
                if ($d['precio_unitario'] > 0) {
                    $m->costo_promedio = round(($stock * $m->costo_promedio + $aceptada * $d['precio_unitario']) / max($stock + $aceptada, 0.01), 2);
                    $m->save();
                }
                $inv->mover($m->id_material, 'ENTRADA', $aceptada, $aceptada, null,
                    "Compra a {$p->razon_social} · S/ " . number_format($d['precio_unitario'], 2) . ' c/u' . ($rech ? " · $rech {$m->unidad} rechazados por falla" : ''));
                // El costo promedio cambió: se actualiza el costo guardado de las órdenes que usan ese material
                Movimiento::where('id_material', $m->id_material)->whereNotNull('id_orden')->distinct()->pluck('id_orden')
                    ->each(fn ($id) => $inv->actualizarCosto((int) $id));
            }
        });
        return redirect()->route('compras')->with('ok', $aceptada > 0
            ? "Entrada registrada: +$aceptada {$m->unidad} de {$m->nombre}" . ($rech ? " ($rech rechazados)." : '.')
            : 'Todo lo recibido fue rechazado; no se sumó stock.');
    }
}
