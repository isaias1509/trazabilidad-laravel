<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $table = 'compras';
    protected $primaryKey = 'id_compra';
    public $timestamps = false;
    protected $guarded = [];

    public function detalles() { return $this->hasMany(DetalleCompra::class, 'id_compra'); }
    public function proveedor() { return $this->belongsTo(Proveedor::class, 'id_proveedor'); }
}
