<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DirectorioController;
use App\Http\Controllers\HoyController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', HoyController::class)->name('hoy');

    Route::get('/ordenes', [OrdenController::class, 'index'])->name('ordenes');
    Route::post('/ordenes', [OrdenController::class, 'store'])->name('ordenes.store');
    Route::get('/ordenes/{orden}', [OrdenController::class, 'show'])->name('ordenes.show');
    foreach (['entregar', 'devolver', 'merma', 'estado', 'reasignar', 'cancelar'] as $accion) {
        Route::post("/ordenes/{orden}/$accion", [OrdenController::class, $accion])->name("ordenes.$accion");
    }

    Route::get('/materiales', [MaterialController::class, 'index'])->name('materiales');
    Route::post('/materiales', [MaterialController::class, 'store'])->name('materiales.store');
    Route::post('/materiales/{material}/ajuste', [MaterialController::class, 'ajustar'])->name('materiales.ajustar');

    Route::get('/compras', [CompraController::class, 'index'])->name('compras');
    Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');

    Route::get('/kardex', [KardexController::class, 'index'])->name('kardex');
    Route::post('/kardex/{movimiento}/anular', [KardexController::class, 'anular'])->name('kardex.anular');

    Route::get('/reportes', ReporteController::class)->name('reportes');

    Route::get('/directorio', [DirectorioController::class, 'index'])->name('directorio');
    Route::post('/directorio/proveedores', [DirectorioController::class, 'proveedor'])->name('directorio.proveedor');
    Route::post('/directorio/costureros', [DirectorioController::class, 'costurero'])->name('directorio.costurero');
});
