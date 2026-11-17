<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    protected $table = 'ordenes';
    protected $primaryKey = 'id_orden';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['fecha_inicio' => 'date', 'fecha_limite' => 'date', 'costo_total' => 'float'];

    /** Flujo normal de estados (CANCELADA va aparte) */
    public const ESTADOS = ['PENDIENTE', 'EN_CORTE', 'EN_CONFECCION', 'EN_ESTAMPADO', 'TERMINADO', 'ENTREGADO'];
    public const ETIQUETAS = [
        'PENDIENTE' => 'Pendiente', 'EN_CORTE' => 'En corte', 'EN_CONFECCION' => 'En confección',
        'EN_ESTAMPADO' => 'En estampado', 'TERMINADO' => 'Terminado', 'ENTREGADO' => 'Entregado', 'CANCELADA' => 'Cancelada',
    ];

    public function pedido() { return $this->belongsTo(Pedido::class, 'id_pedido'); }
    public function materiales() { return $this->hasMany(MaterialOrden::class, 'id_orden'); }
    public function asignaciones() { return $this->hasMany(Asignacion::class, 'id_orden')->orderBy('id_asignacion'); }
    public function historial() { return $this->hasMany(EstadoOrden::class, 'id_orden')->orderBy('fecha'); }
    public function movimientos() { return $this->hasMany(Movimiento::class, 'id_orden')->orderBy('fecha'); }

    public function getCodigoAttribute(): string { return 'OP-' . str_pad($this->id_orden, 3, '0', STR_PAD_LEFT); }
    public function getIndiceAttribute(): int { $i = array_search($this->estado, self::ESTADOS, true); return $i === false ? -1 : $i; }
    public function getCanceladaAttribute(): bool { return $this->estado === 'CANCELADA'; }
    public function getCerradaAttribute(): bool { return in_array($this->estado, ['ENTREGADO', 'CANCELADA'], true); }
    public function getEnCursoAttribute(): bool { return $this->indice >= 0 && $this->indice < 4; }
    public function getCostureroActualAttribute(): ?Costurero { return optional($this->asignaciones->last())->costurero; }

    /** Situación respecto a la fecha de entrega: [clase, texto] */
    public function getSituacionAttribute(): array
    {
        if ($this->cancelada) return ['cancel', 'Cancelada'];
        if ($this->indice >= 4) return ['ok', $this->estado === 'ENTREGADO' ? 'Entregada' : 'Terminada'];
        $d = (int) Carbon::today()->diffInDays($this->fecha_limite->copy()->startOfDay(), false);
        if ($d < 0) return ['bad', 'Atrasada ' . abs($d) . (abs($d) === 1 ? ' día' : ' días')];
        if ($d <= 3) return ['warn', $d === 0 ? 'Vence hoy' : "Vence en $d " . ($d === 1 ? 'día' : 'días')];
        return ['neutral', "Entrega en $d días"];
    }
}
