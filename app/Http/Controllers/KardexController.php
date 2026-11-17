<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Movimiento;
use App\Services\Inventario;
use Illuminate\Http\Request;

class KardexController extends Controller
{
    public function index(Request $r)
    {
        $materiales = Material::orderBy('nombre')->get();
        $m = $materiales->firstWhere('id_material', (int) $r->query('material')) ?? $materiales->first();
        $movs = $m ? Movimiento::with(['usuario', 'orden'])->where('id_material', $m->id_material)->orderByDesc('id_movimiento')->paginate(30)->withQueryString() : collect();
        return view('kardex', ['materiales' => $materiales, 'm' => $m, 'movs' => $movs, 'confirmar' => (int) $r->query('anular')]);
    }

    public function anular(Request $r, Movimiento $movimiento, Inventario $inv)
    {
        $this->permitir('anular');
        $d = $r->validate(['motivo' => 'required|string|max:100'], ['motivo.required' => 'Escribe el motivo de la anulación.']);
        $inv->anular($movimiento, $d['motivo']);
        return redirect()->route('kardex', ['material' => $movimiento->id_material])->with('ok', 'Movimiento anulado. Quedan ambos registros en el kardex.');
    }
}
