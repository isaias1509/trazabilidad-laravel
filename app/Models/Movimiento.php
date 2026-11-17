<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    protected $table = 'movimientos';
    protected $primaryKey = 'id_movimiento';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['fecha' => 'datetime', 'cantidad' => 'float', 'efecto' => 'float', 'saldo' => 'float', 'anulado' => 'boolean'];

    /** Etiqueta y color de cada tipo de movimiento */
    public const TIPOS = [
        'ENTRADA'      => ['Entrada', 'ok'],
        'SALIDA_ORDEN' => ['Salida a orden', 'brand'],
        'DEVOLUCION'   => ['Devolución', 'ok'],
        'MERMA'        => ['Merma en taller', 'warn'],
        'AJUSTE'       => ['Ajuste', 'neutral'],
        'ANULACION'    => ['Anulación', 'cancel'],
    ];

    public function material() { return $this->belongsTo(Material::class, 'id_material'); }
    public function orden() { return $this->belongsTo(Orden::class, 'id_orden'); }
    public function usuario() { return $this->belongsTo(User::class, 'id_usuario'); }

    /** Movimientos que cuentan para una orden: ni anulados ni anulaciones */
    public function scopeVigentes($q) { return $q->where('anulado', false)->where('tipo', '!=', 'ANULACION'); }
}
