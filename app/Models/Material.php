<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table = 'materiales';
    protected $primaryKey = 'id_material';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['stock_actual' => 'float', 'stock_minimo' => 'float', 'costo_promedio' => 'float'];

    public const TIPOS = ['TELA' => 'Tela', 'INSUMO' => 'Insumo', 'EMPAQUE' => 'Empaque', 'CONSUMIBLE' => 'Consumible'];

    public function movimientos() { return $this->hasMany(Movimiento::class, 'id_material'); }

    public function getBajoAttribute(): bool { return $this->stock_actual <= $this->stock_minimo; }

    public function getCodigoAttribute(): string { return 'MAT-' . str_pad($this->id_material, 3, '0', STR_PAD_LEFT); }
}
