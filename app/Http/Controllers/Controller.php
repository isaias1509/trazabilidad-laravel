<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** Corta la acción si el rol del usuario no la tiene permitida (config/permisos.php). */
    protected function permitir(string $accion): void
    {
        abort_unless(auth()->user()?->puede($accion), 403, 'Tu rol no tiene permiso para esta acción.');
    }
}
