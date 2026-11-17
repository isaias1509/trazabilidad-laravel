<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';
    protected $primaryKey = 'id_pedido';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['fecha_entrega' => 'date'];
    public function producto() { return $this->belongsTo(Producto::class, 'id_producto'); }
    public function orden() { return $this->hasOne(Orden::class, 'id_pedido'); }
}
