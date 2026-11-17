<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialOrden extends Model
{
    protected $table = 'materiales_orden';
    protected $primaryKey = 'id_material_orden';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['cantidad_requerida' => 'float', 'cantidad_entregada' => 'float'];

    public function material() { return $this->belongsTo(Material::class, 'id_material'); }
}
