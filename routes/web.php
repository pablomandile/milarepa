<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TipoActividadController;
use App\Http\Controllers\LugaresController;
use App\Http\Controllers\DisponibilidadesController;
use App\Http\Controllers\MaestrosController;
use App\Http\Controllers\CoordinadoresController;
use App\Http\Controllers\MonedasController;
use App\Http\Controllers\MetodosPagoController;
use App\Http\Controllers\EsquemaDescuentosController;
use App\Http\Controllers\EsquemasPrecioController;
use App\Http\Controllers\AplicaDescuentoLugaresController;


Route::get('/', [DashboardController::class, 'index']);

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified',])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::resource('/lugares', LugaresController::class);
    Route::resource('/tipo-actividad', TipoActividadController::class);
    Route::resource('/disponibilidades', DisponibilidadesController::class);
    Route::resource('/maestros', MaestrosController::class);
    Route::resource('/coordinadores', CoordinadoresController::class);
    Route::resource('/monedas', MonedasController::class);
    Route::resource('/metodos-pago', MetodosPagoController::class);
    Route::resource('/esquema-precios', EsquemasPrecioController::class);
    Route::resource('/aplica-descuento-lugares', AplicaDescuentoLugaresController::class);
    Route::resource('/esquema-descuentos', EsquemaDescuentosController::class);





});
