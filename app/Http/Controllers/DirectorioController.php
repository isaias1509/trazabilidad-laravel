<?php

namespace App\Http\Controllers;

use App\Models\Costurero;
use App\Models\Proveedor;
use Illuminate\Http\Request;

/** Directorio: proveedores y costureros (RF-03). Lo ve todo usuario; solo registran Administrador y Control de materiales. */
class DirectorioController extends Controller
{
    public function index()
    {
        return view('directorio', [
            'proveedores' => Proveedor::withCount('compras')->orderBy('razon_social')->get(),
            'costureros' => Costurero::withCount('asignaciones')->orderBy('nombre')->get(),
        ]);
    }

    public function proveedor(Request $r)
    {
        $this->permitir('directorio');
        $d = $r->validate([
            'razon_social' => 'required|string|max:150|unique:proveedores,razon_social',
            'ruc' => 'nullable|digits:11|unique:proveedores,ruc',
            'telefono' => 'nullable|string|max:20',
            'rubro' => 'nullable|string|max:80',
        ], [
            'razon_social.required' => 'Escribe la razón social del proveedor.',
            'razon_social.unique' => 'Ya existe un proveedor con esa razón social.',
            'ruc.digits' => 'El RUC debe tener 11 dígitos.',
            'ruc.unique' => 'Ya existe un proveedor con ese RUC.',
        ]);
        $p = Proveedor::create($d);
        return redirect()->route('directorio')->with('ok', "Proveedor {$p->razon_social} registrado.");
    }

    public function costurero(Request $r)
    {
        $this->permitir('directorio');
        $d = $r->validate([
            'nombre' => 'required|string|max:100|unique:costureros,nombre',
            'zona' => 'nullable|string|max:60',
            'telefono' => 'nullable|string|max:20',
        ], [
            'nombre.required' => 'Escribe el nombre de la costurera o costurero.',
            'nombre.unique' => 'Ya existe una costurera o costurero con ese nombre.',
        ]);
        $c = Costurero::create($d);
        return redirect()->route('directorio')->with('ok', "Registrado: {$c->nombre}. Ya se le pueden asignar órdenes.");
    }
}
