<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Costurero extends Model
{
    protected $table = 'costureros';
    protected $primaryKey = 'id_costurero';
    public $timestamps = false;
    protected $guarded = [];

    public function asignaciones() { return $this->hasMany(Asignacion::class, 'id_costurero'); }
}
