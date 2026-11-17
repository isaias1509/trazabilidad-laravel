<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use App\Services\Inventario;

class ReporteController extends Controller
{
    public function __invoke(Inventario $inv)
    {
        $filas = Orden::with(['pedido', 'asignaciones.costurero'])->where('estado', '!=', 'CANCELADA')->orderBy('id_orden')->get()
            ->map(function ($o) use ($inv) {
                $telas = $inv->resumenOrden($o)->filter(fn ($f) => $f->material->tipo === 'TELA');
                $neto = round($telas->sum('neto'), 2);
                $merma = round($telas->sum('merma'), 2);
                return (object) ['o' => $o, 'requerido' => round($telas->sum('requerido'), 2), 'neto' => $neto, 'merma' => $merma,
                    'pct' => $neto ? round($merma / $neto * 100, 2) : 0, 'diferencia' => round($neto - $telas->sum('requerido'), 2),
                    'costo' => (float) $o->costo_total, 'costurera' => $o->costurero_actual->nombre ?? 'Sin asignar'];
            })->filter(fn ($f) => $f->neto > 0)->values();
        $porCosturera = $filas->groupBy('costurera')->map(fn ($g) => (object) [
            'ordenes' => $g->count(), 'neto' => round($g->sum('neto'), 2), 'merma' => round($g->sum('merma'), 2),
            'pct' => $g->sum('neto') ? round($g->sum('merma') / $g->sum('neto') * 100, 2) : 0,
        ]);
        return view('reportes', compact('filas', 'porCosturera'));
    }
}
