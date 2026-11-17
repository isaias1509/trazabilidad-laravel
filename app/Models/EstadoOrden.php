<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoOrden extends Model
{
    protected $table = 'estados_orden';
    protected $primaryKey = 'id_estado';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['fecha' => 'datetime'];
    public function usuario() { return $this->belongsTo(User::class, 'id_usuario'); }
}
