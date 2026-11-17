<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asignacion extends Model
{
    protected $table = 'asignaciones';
    protected $primaryKey = 'id_asignacion';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['fecha' => 'date'];
    public function costurero() { return $this->belongsTo(Costurero::class, 'id_costurero'); }
}
