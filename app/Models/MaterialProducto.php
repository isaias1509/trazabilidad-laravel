<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialProducto extends Model
{
    protected $table = 'materiales_producto';
    protected $primaryKey = 'id_material_producto';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['consumo_unitario' => 'float'];

    public function material() { return $this->belongsTo(Material::class, 'id_material'); }
}
