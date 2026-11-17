<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** Formatos para las vistas (números, soles y fechas en formato peruano). */
class Fmt
{
    public static function n($x): string
    {
        $s = number_format((float) $x, 2, '.', ',');
        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }

    public static function sol($x): string { return 'S/ ' . number_format((float) $x, 2, '.', ','); }

    public static function fecha($f, bool $hora = false): string
    {
        if (!$f) return '—';
        $f = $f instanceof CarbonInterface ? $f : \Illuminate\Support\Carbon::parse($f);
        return $f->format($hora ? 'd/m/Y H:i' : 'd/m/Y');
    }
}
