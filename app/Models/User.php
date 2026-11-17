<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Usuario del sistema (tabla "usuarios" del modelo físico).
 * Inicia sesión con su correo y contraseña; su rol define qué puede hacer (config/permisos.php).
 */
class User extends Authenticatable
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;
    protected $guarded = [];
    protected $hidden = ['contrasena', 'remember_token'];

    public const ROLES = [
        'ADMINISTRADOR' => 'Administrador', 'CONTROL_MATERIALES' => 'Control de materiales',
        'ALMACEN' => 'Almacén', 'VENTAS' => 'Ventas', 'CONSULTA' => 'Consulta',
    ];

    public function getAuthPassword() { return $this->contrasena; }
    public function getAuthPasswordName() { return 'contrasena'; }

    public function getRolNombreAttribute(): string { return self::ROLES[$this->rol] ?? $this->rol; }

    /** ¿Este usuario puede hacer la acción? */
    public function puede(string $accion): bool
    {
        return in_array($accion, config('permisos.' . $this->rol, []), true);
    }
}
