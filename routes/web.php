<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TiposActividadController;
use App\Http\Controllers\EntidadesController;
use App\Http\Controllers\DisponibilidadesController;
use App\Http\Controllers\MaestrosController;
use App\Http\Controllers\MembresiasController;
use App\Http\Controllers\CoordinadoresController;
use App\Http\Controllers\MonedasController;
use App\Http\Controllers\MetodosPagoController;
use App\Http\Controllers\EsquemaDescuentosController;
use App\Http\Controllers\EsquemaPreciosController;
use App\Http\Controllers\PerfilesController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\ComidasController;
use App\Http\Controllers\HospedajesController;
use App\Http\Controllers\LugaresHospedajeController;
use App\Http\Controllers\ModalidadesController;
use App\Http\Controllers\InscripcionesController;
use App\Http\Controllers\EstadoInscripcionesController;
use App\Http\Controllers\CentroAyudaController;
use App\Http\Controllers\NovedadesController;
use App\Http\Controllers\ReporteErrorController;
use App\Http\Controllers\AcercaDeController;
use App\Http\Controllers\TicketsController;
use App\Http\Controllers\TransportesController;
use App\Http\Controllers\DescripcionesController;
use App\Http\Controllers\ProgramasController;
use App\Http\Controllers\ActividadesController;
use App\Http\Controllers\GrabacionesController;
use App\Http\Controllers\GridActividadesController;
use App\Http\Controllers\ImagenesController;
use App\Http\Controllers\RegistroMembresiasController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\StreamsController;
use App\Http\Controllers\ProfileCompletionController;


Route::get('/', [DashboardController::class, 'index']);

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified',])->group(function () {
    Route::get('/complete-profile', [ProfileCompletionController::class, 'create'])
        ->name('profile.complete');
    Route::post('/complete-profile', [ProfileCompletionController::class, 'store'])
        ->name('profile.complete.store');
    Route::get('/complete-profile/edit', [ProfileCompletionController::class, 'edit'])
        ->name('profile.complete.edit'); // Modo "editar" (updating=true)
    Route::put('/complete-profile', [ProfileCompletionController::class, 'update'])
        ->name('profile.complete.update');

    Route::get('/dashboard', function () {
        $user = auth()->user();
        // Chequeas si el perfil está incompleto
        if (is_null($user->telefono)) {
            return redirect()->route('profile.complete');
        }
        return inertia('Dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::resource('entidades', EntidadesController::class, [
        'parameters' => ['entidades' => 'entidad'], ]); // Renombrar el parámetro a 'entidad' por singular español
    Route::resource('/tiposactividad', TiposActividadController::class, [
        'parameters' => ['tiposactividad' => 'tipoactividad'],]);
    Route::resource('/disponibilidades', DisponibilidadesController::class, [
        'parameters' => ['disponibilidades' => 'disponibilidad'],]);
    Route::resource('/maestros', MaestrosController::class);
    Route::resource('/coordinadores', CoordinadoresController::class , [
        'parameters' => ['coordinadores' => 'coordinador'],]);
    Route::resource('/monedas', MonedasController::class);
    Route::resource('/metodospago', MetodosPagoController::class, [
        'parameters' => ['metodospago' => 'metodopago'],]);
    Route::resource('/actividades', ActividadesController::class, [
        'parameters' => ['actividades' => 'actividad'],]);
    Route::resource('/grid-actividades', GridActividadesController::class, [
        'parameters' => ['grid-actividades' => 'grid-actividad'],]);
    Route::resource('/usuarios', UsuariosController::class);
    Route::resource('/perfiles', PerfilesController::class);
    Route::resource('/roles', RolesController::class);
    Route::resource('/membresias', MembresiasController::class);
    Route::get('/membresias-gestion', [MembresiasController::class, 'gestion'])->name('membresias.gestion');
    Route::resource('/comidas', ComidasController::class);
    Route::resource('/hospedajes', HospedajesController::class);
    Route::resource('/transportes', TransportesController::class);
    Route::resource('/inscripciones', InscripcionesController::class, [
        'parameters' => ['inscripciones' => 'inscripcion'],]);
    Route::resource('/estadoinscripciones', EstadoInscripcionesController::class, [
        'parameters' => ['estadoinscripciones' => 'estadoinscripcion'],]);
    Route::resource('/lugareshospedaje', LugaresHospedajeController::class, [
        'parameters' => ['lugareshospedaje' => 'lugarhospedaje'],]);
    Route::resource('/modalidades', ModalidadesController::class, [
        'parameters' => ['modalidades' => 'modalidad'],]);
    Route::resource('/centroayuda', CentroAyudaController::class);
    Route::get('/novedades/gestion', [NovedadesController::class, 'gestion'])->name('novedades.gestion');
    Route::resource('/novedades', NovedadesController::class, [
        'parameters' => ['novedades' => 'novedad'],]);
    Route::resource('/reporteerror', ReporteErrorController::class);
    Route::resource('/acercade', AcercaDeController::class);
    Route::put('/tickets/asignar/{ticket}', [TicketsController::class, 'asignar'])->name('tickets.asignar');
    Route::resource('/descripciones', DescripcionesController::class, [
        'parameters' => ['descripciones' => 'descripcion'],]);
    Route::resource('/programas', ProgramasController::class);
    Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

    Route::resource('/esquemaprecios', EsquemaPreciosController::class);
    Route::resource('/esquemadescuentos', EsquemaDescuentosController::class);

    Route::post('/esquema-precios/{id}/membresias', [EsquemaPreciosController::class, 'storeMembresia'])
    ->name('esquemaprecios.storeMembresia');
    Route::post('/esquema-descuentos/{id}/membresias', [EsquemaDescuentosController::class, 'storeMembresia'])
    ->name('esquemadescuentos.storeMembresia');

    // Editar y eliminar membershipLine
    Route::put('/esquema-precios/membresias/{membershipLineId}', [EsquemaPreciosController::class, 'updateMembresia'])
    ->name('esquemaprecios.updateMembresia');
    Route::delete('/esquema-precios/membresias/{membershipLineId}', [EsquemaPreciosController::class, 'destroyMembresia'])
    ->name('esquemaprecios.destroyMembresia');

    Route::put('/esquema-descuentos/membresias/{membershipLineId}', [EsquemaDescuentosController::class, 'updateMembresia'])
    ->name('esquemadescuentos.updateMembresia');
    Route::delete('/esquema-descuentos/membresias/{membershipLineId}', [EsquemaDescuentosController::class, 'destroyMembresia'])
    ->name('esquemadescuentos.destroyMembresia');

    Route::resource('/streams', StreamsController::class);
    Route::post('/streams/{id}/links', [StreamsController::class, 'storeLink'])
    ->name('streams.storeLink');
    Route::put('/streams/links/{linkLineId}', [StreamsController::class, 'updateLink'])
    ->name('streams.updateLink');
    Route::delete('/streams/links/{linkLineId}', [StreamsController::class, 'destroyLink'])
    ->name('streams.destroyLink');

    Route::resource('/grabaciones', GrabacionesController::class, [
        'parameters' => ['grabaciones' => 'grabacion'],]);
    Route::post('/grabaciones/{id}/links', [GrabacionesController::class, 'storeLink'])
    ->name('grabaciones.storeLink');
    Route::put('/grabaciones/links/{linkLineId}', [GrabacionesController::class, 'updateLink'])
    ->name('grabaciones.updateLink');
    Route::delete('/grabaciones/links/{linkLineId}', [GrabacionesController::class, 'destroyLink'])
    ->name('grabaciones.destroyLink');
    
    Route::resource('/registromembresias', RegistroMembresiasController::class);

    Route::resource('/imagenes', ImagenesController::class, [
        'parameters' => ['imagenes' => 'imagen'],]);

    Route::post('/imagenes-json', [ImagenesController::class, 'storeJson'])->name('imagenes.json');

});
