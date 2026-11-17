<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';
    protected $primaryKey = 'id_producto';
    public $timestamps = false;
    protected $guarded = [];

    public function receta() { return $this->hasMany(MaterialProducto::class, 'id_producto'); }
}
