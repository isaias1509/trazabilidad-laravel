<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialOrden;
use App\Models\Movimiento;
use App\Models\Orden;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas del inventario y de la trazabilidad.
 * Todo cambio de stock pasa por mover(): bloquea la fila del material,
 * valida que el stock no quede negativo y deja el movimiento en el kardex con su saldo.
 * Nada se borra: los errores se corrigen con anular().
 */
class Inventario
{
    public function mover(int $idMaterial, string $tipo, float $cantidad, float $efecto, ?int $idOrden = null,
                          ?string $motivo = null, ?int $idAnulado = null): Movimiento
    {
        return DB::transaction(function () use ($idMaterial, $tipo, $cantidad, $efecto, $idOrden, $motivo, $idAnulado) {
            $m = Material::lockForUpdate()->findOrFail($idMaterial);
            $nuevo = round($m->stock_actual + $efecto, 2);
            if ($nuevo < 0) {
                throw ValidationException::withMessages([
                    'cantidad' => "Stock insuficiente: solo hay {$this->n($m->stock_actual)} {$m->unidad} de {$m->nombre}.",
                ]);
            }
            $m->stock_actual = $nuevo;
            $m->save();

            if ($idOrden && in_array($tipo, ['SALIDA_ORDEN', 'DEVOLUCION', 'ANULACION'], true) && $efecto != 0) {
                $mo = MaterialOrden::firstOrCreate(['id_orden' => $idOrden, 'id_material' => $idMaterial], ['cantidad_requerida' => 0]);
                $mo->cantidad_entregada = round($mo->cantidad_entregada - $efecto, 2);
                $mo->save();
            }

            $mov = Movimiento::create([
                'id_material' => $idMaterial, 'id_orden' => $idOrden, 'id_usuario' => Auth::id(),
                'tipo' => $tipo, 'cantidad' => round($cantidad, 2), 'efecto' => round($efecto, 2),
                'saldo' => $nuevo, 'motivo' => $motivo, 'id_movimiento_anulado' => $idAnulado, 'fecha' => now(),
            ]);

            // El costo de la orden se recalcula solo después de cada movimiento
            if ($idOrden) $this->actualizarCosto($idOrden);

            return $mov;
        });
    }

    /** Anula un movimiento creando el contrario. El original queda marcado, no se borra. */
    public function anular(Movimiento $x, string $motivo): Movimiento
    {
        return DB::transaction(function () use ($x, $motivo) {
            // Se vuelve a leer con bloqueo: si dos personas anulan a la vez, la segunda espera y ve que ya está anulado
            $x = Movimiento::lockForUpdate()->findOrFail($x->id_movimiento);
            if ($x->anulado || $x->tipo === 'ANULACION') {
                throw ValidationException::withMessages(['motivo' => 'Este movimiento ya fue anulado o es una anulación.']);
            }

            // Candado: no se puede anular una entrega si parte de ese material ya se devolvió o se mermó
            if ($x->tipo === 'SALIDA_ORDEN' && $x->id_orden) {
                Material::lockForUpdate()->find($x->id_material);
                $f = $this->resumenOrden(Orden::findOrFail($x->id_orden))->firstWhere('material.id_material', $x->id_material);
                $enPoder = $f ? $f->en_poder : 0;
                if ($x->cantidad > $enPoder + 0.001) {
                    throw ValidationException::withMessages(['motivo' =>
                        "No se puede anular esta entrega completa: la orden solo tiene {$this->n($enPoder)} {$f?->material->unidad} de ese material "
                        . '(ya se devolvió o se mermó una parte). Anula primero esas devoluciones o mermas.']);
                }
            }

            $x->anulado = true;
            $x->save();
            return $this->mover($x->id_material, 'ANULACION', $x->cantidad, -$x->efecto, $x->id_orden,
                'Anula el movimiento del ' . $x->fecha->format('d/m/Y H:i') . ' · ' . $motivo, $x->id_movimiento);
        });
    }

    /** Material que la orden tiene realmente en el taller: lo entregado menos lo devuelto y la merma */
    public function enPoder(Orden $o, int $idMaterial): float
    {
        $f = $this->resumenOrden($o)->firstWhere('material.id_material', $idMaterial);
        return $f ? $f->en_poder : 0.0;
    }

    /** Guarda en la orden el costo de sus materiales (costo_total) */
    public function actualizarCosto(int $idOrden): void
    {
        $o = Orden::find($idOrden);
        if ($o) {
            $o->costo_total = $this->costoOrden($o);
            $o->save();
        }
    }

    /**
     * Resumen de materiales de una orden: requerido, entregado, devuelto, merma y neto.
     * @return Collection<int, object>
     */
    public function resumenOrden(Orden $o): Collection
    {
        $filas = [];
        foreach ($o->materiales()->with('material')->get() as $mo) {
            $filas[$mo->id_material] = (object) ['material' => $mo->material, 'requerido' => (float) $mo->cantidad_requerida,
                'entregado' => 0.0, 'devuelto' => 0.0, 'merma' => 0.0];
        }
        $movs = Movimiento::vigentes()->where('id_orden', $o->id_orden)->with('material')->get();
        foreach ($movs as $x) {
            $f = $filas[$x->id_material] ??= (object) ['material' => $x->material, 'requerido' => 0.0, 'entregado' => 0.0, 'devuelto' => 0.0, 'merma' => 0.0];
            if ($x->tipo === 'SALIDA_ORDEN') $f->entregado += $x->cantidad;
            if ($x->tipo === 'DEVOLUCION')   $f->devuelto  += $x->cantidad;
            if ($x->tipo === 'MERMA')        $f->merma     += $x->cantidad;
        }
        return collect($filas)->map(function ($f) {
            $f->neto = round($f->entregado - $f->devuelto, 2);
            $f->en_poder = round(max(0, $f->neto - $f->merma), 2);
            $f->diferencia = $f->requerido ? round($f->neto - $f->requerido, 2) : null;
            return $f;
        })->values();
    }

    public function costoOrden(Orden $o): float
    {
        return round($this->resumenOrden($o)->sum(fn ($f) => $f->neto * $f->material->costo_promedio), 2);
    }

    /** Cuánto material necesitan todavía las órdenes en curso */
    public function pendiente(int $idMaterial): float
    {
        return round(MaterialOrden::query()
            ->join('ordenes', 'ordenes.id_orden', '=', 'materiales_orden.id_orden')
            ->whereIn('ordenes.estado', ['PENDIENTE', 'EN_CORTE', 'EN_CONFECCION', 'EN_ESTAMPADO'])
            ->where('materiales_orden.id_material', $idMaterial)
            ->get(['materiales_orden.cantidad_requerida', 'materiales_orden.cantidad_entregada'])
            ->sum(fn ($r) => max(0, $r->cantidad_requerida - $r->cantidad_entregada)), 2);
    }

    /** Compra sugerida: dos veces el mínimo + lo pendiente de las órdenes - el stock */
    public function sugerido(Material $m): float
    {
        return round(max(0, $m->stock_minimo * 2 + $this->pendiente($m->id_material) - $m->stock_actual), 2);
    }

    private function n(float $x): string { return rtrim(rtrim(number_format($x, 2, '.', ''), '0'), '.'); }
}
