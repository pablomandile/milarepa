<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TipoActividadController;
use App\Http\Controllers\EntidadesController;
use App\Http\Controllers\DisponibilidadesController;
use App\Http\Controllers\MaestrosController;
use App\Http\Controllers\MembresiasController;
use App\Http\Controllers\CoordinadoresController;
use App\Http\Controllers\MonedasController;
use App\Http\Controllers\MetodosPagoController;
use App\Http\Controllers\EsquemaDescuentosController;
use App\Http\Controllers\EsquemasPrecioController;
use App\Http\Controllers\AplicaDescuentoLugaresController;
use App\Http\Controllers\PerfilesController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsuariosController;

Route::get('/', [DashboardController::class, 'index']);

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified',])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::resource('entidades', EntidadesController::class, [
        'parameters' => ['entidades' => 'entidad'], // Renombrar el parámetro a 'entidad' por singular español
    ]);
    Route::resource('/tipo-actividad', TipoActividadController::class);
    Route::resource('/disponibilidades', DisponibilidadesController::class, [
        'parameters' => ['disponibilidades' => 'disponibilidad'],]);
    Route::resource('/maestros', MaestrosController::class);
    Route::resource('/coordinadores', CoordinadoresController::class , [
        'parameters' => ['coordinadores' => 'coordinador'],]);
    Route::resource('/monedas', MonedasController::class);
    Route::resource('/metodos-pago', MetodosPagoController::class, [
        'parameters' => ['metodos-pago' => 'metodo-pago'],]);
    Route::resource('/esquema-precios', EsquemasPrecioController::class);
    Route::resource('/aplica-descuento-lugares', AplicaDescuentoLugaresController::class);
    Route::resource('/esquema-descuentos', EsquemaDescuentosController::class);
    Route::resource('/actividades', EsquemaDescuentosController::class);
    Route::resource('/usuarios', UsuariosController::class);
    Route::resource('/perfiles', PerfilesController::class);
    Route::resource('/roles', RolesController::class);
    Route::resource('/membresias', MembresiasController::class);
    Route::resource('/comidas', RolesController::class);
    Route::resource('/hospedajes', RolesController::class);
    Route::resource('/transportes', RolesController::class);
    Route::resource('/inscripciones', RolesController::class);
    Route::resource('/estado-inscripciones', RolesController::class);
});
