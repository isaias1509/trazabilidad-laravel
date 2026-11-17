<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Orden;
use App\Services\Inventario;

class HoyController extends Controller
{
    public function __invoke(Inventario $inv)
    {
        $ordenes = Orden::with(['pedido.producto', 'asignaciones.costurero'])->get();
        $enCurso = $ordenes->filter->en_curso->sortBy('fecha_limite');
        $atrasadas = $enCurso->filter(fn ($o) => $o->situacion[0] === 'bad');
        $porVencer = $enCurso->filter(fn ($o) => $o->situacion[0] === 'warn');
        $bajos = Material::whereColumn('stock_actual', '<=', 'stock_minimo')->orderBy('nombre')->get()
            ->each(fn ($m) => $m->sugerido = $inv->sugerido($m));
        return view('hoy', compact('enCurso', 'atrasadas', 'porVencer', 'bajos'));
    }
}
