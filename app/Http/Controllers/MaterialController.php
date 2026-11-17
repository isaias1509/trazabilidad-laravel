<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\Inventario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaterialController extends Controller
{
    public const MOTIVOS_AJUSTE = ['Conteo físico no cuadra', 'Merma o daño en almacén', 'Pérdida', 'Error de registro anterior', 'Otro'];

    public function index(Request $r)
    {
        return view('materiales', [
            'materiales' => Material::orderBy('tipo')->orderBy('nombre')->get(),
            'ajuste' => (int) $r->query('ajuste'),
            'motivos' => self::MOTIVOS_AJUSTE,
        ]);
    }

    public function store(Request $r)
    {
        $this->permitir('material');
        $d = $r->validate([
            'nombre' => 'required|string|max:120|unique:materiales,nombre',
            'tipo' => ['required', Rule::in(array_keys(Material::TIPOS))],
            'unidad' => 'required|string|max:20',
            'stock_minimo' => 'required|numeric|min:0',
            'costo_promedio' => 'nullable|numeric|min:0',
        ], ['nombre.unique' => 'Ya existe un material con ese nombre.', 'stock_minimo.min' => 'El stock mínimo no puede ser negativo.']);
        $m = Material::create($d + ['stock_actual' => 0, 'costo_promedio' => $d['costo_promedio'] ?? 0]);
        return redirect()->route('materiales')->with('ok', "{$m->nombre} registrado. Regístrale una compra para sumar stock.");
    }

    /** Ajuste por conteo físico: la diferencia entra al kardex con su motivo */
    public function ajustar(Request $r, Material $material, Inventario $inv)
    {
        $this->permitir('ajustar');
        $d = $r->validate(['contado' => 'required|numeric|min:0', 'motivo' => ['required', Rule::in(self::MOTIVOS_AJUSTE)], 'detalle' => 'nullable|string|max:80']);
        $dif = round($d['contado'] - $material->stock_actual, 2);
        if ($dif == 0) return redirect()->route('materiales')->with('ok', 'El conteo coincide con el sistema; no hay nada que ajustar.');
        $inv->mover($material->id_material, 'AJUSTE', abs($dif), $dif, null,
            "{$d['motivo']} · contado {$d['contado']}, el sistema decía {$material->stock_actual}" . (!empty($d['detalle']) ? " · {$d['detalle']}" : ''));
        return redirect()->route('materiales')->with('ok', 'Ajuste guardado: ' . ($dif > 0 ? '+' : '') . "$dif {$material->unidad} de {$material->nombre}.");
    }
}
