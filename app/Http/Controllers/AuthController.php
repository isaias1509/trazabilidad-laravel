<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function form() { return view('auth.login'); }

    public function login(Request $r)
    {
        $datos = $r->validate(['correo' => 'required|email', 'password' => 'required'],
            ['correo.required' => 'Escribe tu correo.', 'correo.email' => 'El correo no es válido.', 'password.required' => 'Escribe tu contraseña.']);
        if (Auth::attempt(['correo' => $datos['correo'], 'password' => $datos['password'], 'activo' => 1])) {
            $r->session()->regenerate();
            return redirect()->intended(route('hoy'));
        }
        return back()->withErrors(['correo' => 'Correo o contraseña incorrectos.'])->onlyInput('correo');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }
}
