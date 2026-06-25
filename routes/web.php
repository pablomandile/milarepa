<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Models\User;
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
use App\Http\Controllers\PrecioGruposController;
use App\Http\Controllers\PerfilesController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\ComidasController;
use App\Http\Controllers\HospedajesController;
use App\Http\Controllers\LugaresHospedajeController;
use App\Http\Controllers\ModalidadesController;
use App\Http\Controllers\InscripcionesController;
use App\Http\Controllers\EstadoInscripcionesController;
use App\Http\Controllers\InvitadosController;
use App\Http\Controllers\CentroAyudaController;
use App\Http\Controllers\NovedadesController;
use App\Http\Controllers\ReporteErrorController;
use App\Http\Controllers\AcercaDeController;
use App\Http\Controllers\VersionesController;
use App\Http\Controllers\TicketsController;
use App\Http\Controllers\TransportesController;
use App\Http\Controllers\TutorialesController;
use App\Http\Controllers\DescripcionesController;
use App\Http\Controllers\ProgramasController;
use App\Http\Controllers\ActividadesController;
use App\Http\Controllers\GrabacionesController;
use App\Http\Controllers\GridActividadesController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\OracionesCantadasController;
use App\Http\Controllers\ImagenesController;
use App\Http\Controllers\RegistroMembresiasController;
use App\Http\Controllers\BotonesPagoController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\EstadoCuentaMembresiasController;
use App\Http\Controllers\MembresiasGestionController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\StreamsController;
use App\Http\Controllers\ProfileCompletionController;
use App\Http\Controllers\EmailPreviewController;
use App\Http\Controllers\EmailPlantillasController;
use App\Http\Controllers\EmailEnvioConfiguracionesController;
use App\Http\Controllers\EnvioCorreosController;
use App\Http\Controllers\EnvioActividadesOnlineController;
use App\Http\Controllers\ExcencionPagoController;
use App\Http\Controllers\CiclosController;
use App\Http\Controllers\ClasesController;
use App\Http\Controllers\LugaresController;
use App\Http\Controllers\PaginasActividadesOnlineController;
use App\Http\Controllers\PaginasConfiguracionController;
use App\Http\Controllers\ActividadesOnlineController;
use App\Http\Controllers\MailInfoMembresiasController;
use App\Http\Controllers\ImportarMembresiasController;
use App\Http\Controllers\ImportarInscripcionesController;
use App\Http\Controllers\ProgramaEstudiosController;
use App\Http\Controllers\ProgramaGrabacionController;
use App\Http\Controllers\AsistenciasController;
use App\Http\Controllers\InscripcionesClasesController;
use App\Http\Controllers\LibrosController;
use App\Http\Controllers\InventarioLibrosController;
use App\Http\Controllers\HistoricoPedidosLibrosController;
use App\Http\Controllers\PrestamosAnexosController;
use App\Http\Controllers\InventarioPorEntidadController;
use App\Http\Controllers\DevolucionesAnexosController;
use App\Http\Controllers\VentasLibrosController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\ThemePreferenceController;
use App\Http\Controllers\FrasesDeDharmaController;
use App\Http\Controllers\AreaEstudioController;


Route::get('/', [DashboardController::class, 'index']);

// Google OAuth (Socialite)
Route::middleware('guest')->group(function () {
    Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle'])
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])
        ->name('auth.google.callback');
});
Route::get('/welcome', function () {
    return inertia('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => \Illuminate\Foundation\Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

// Rutas públicas para preview de emails (sin {id}: usan datos dummy, no exponen PII)
// Las versiones con {id} están dentro del grupo auth + role check (más abajo)
Route::get('/email-preview', [EmailPreviewController::class, 'landing'])->name('email.preview.landing');
Route::get('/email-preview/inscripcion', [EmailPreviewController::class, 'inscripcionRegistrada'])
    ->name('preview.email.inscripcion');
Route::get('/email-preview/inscripcion-confirmada', [EmailPreviewController::class, 'inscripcionConfirmada'])
    ->name('preview.email.inscripcion-confirmada');
Route::get('/email-preview/grabacion', [EmailPreviewController::class, 'grabacionDisponible'])
    ->name('preview.email.grabacion');
Route::get('/email-preview/informacion-membresias', [EmailPreviewController::class, 'informacionMembresias'])
    ->name('preview.email.informacion-membresias');
Route::get('/email-preview/inscripcion-tk-registrada', [EmailPreviewController::class, 'inscripcionTkRegistrada'])
    ->name('preview.email.inscripcion-tk-registrada');
Route::get('/email-preview/actividades-online', [EmailPreviewController::class, 'envioActividadesOnline'])
    ->name('preview.email.actividades-online');
Route::get('/email-preview/reporte-semanal-inscripciones-actividad', [EmailPreviewController::class, 'reporteSemanalInscripcionesPorActividad'])
    ->name('preview.email.reporte-semanal-inscripciones-actividad');

// Grid de actividades pÃºblico (solo index + lookup por email)
Route::get('/grid-actividades', [GridActividadesController::class, 'index'])
    ->name('grid-actividades.index');
Route::get('/calendario', [CalendarioController::class, 'index'])
    ->name('calendario.index');
Route::get('/oraciones-cantadas/{oracionCantada}/public', [OracionesCantadasController::class, 'showPublic'])
    ->name('oracionescantadas.show-public');
Route::get('/clases/{clase}/public', [ClasesController::class, 'showPublic'])
    ->name('clases.show-public');
Route::get('/actividades-online', [ActividadesOnlineController::class, 'index'])
    ->name('paginas.actividades-online');
Route::get('/clases-publicas', [ClasesController::class, 'paginaPublica'])
    ->name('paginas.clases');
Route::get('/oraciones-cantadas-publicas', [OracionesCantadasController::class, 'paginaPublica'])
    ->name('paginas.oraciones-cantadas');
Route::get('/grid-actividades/{actividad}/public', [GridActividadesController::class, 'showPublicActividad'])
    ->name('grid-actividades.show-public');
// Rate limit: 5 req/min por IP. Mitiga enumeración masiva desde una única fuente.
// Para defender contra botnets distribuidos ver tarea 2.1c (Turnstile) en backlog.
Route::post('/grid-actividades/lookup-email', [GridActividadesController::class, 'lookupEmail'])
    ->name('grid-actividades.lookup-email')
    ->middleware('throttle:5,1');
Route::post('/grid-actividades/inscribir', [GridActividadesController::class, 'inscribir'])
    ->name('grid-actividades.inscribir')
    ->middleware('throttle:public-write');
Route::post('/grid-actividades/inscribir-guest', [GridActividadesController::class, 'inscribirGuest'])
    ->name('grid-actividades.inscribir-guest')
    ->middleware('throttle:public-write');
Route::post('/grid-actividades/pago/prepare', [GridActividadesController::class, 'preparePago'])
    ->name('grid-actividades.pago.prepare')
    ->middleware('throttle:public-write');
Route::get('/grid-actividades/pago/{actividad}', [GridActividadesController::class, 'pago'])
    ->name('grid-actividades.pago');
Route::post('/grid-actividades/pago/comprobante', [GridActividadesController::class, 'uploadComprobante'])
    ->name('grid-actividades.pago.comprobante')
    ->middleware('throttle:public-write');
Route::post('/grid-actividades/pago/finalizar', [GridActividadesController::class, 'finalizarPago'])
    ->name('grid-actividades.pago.finalizar')
    ->middleware('throttle:public-write');
// URL firmada (signed): solo accesible con la firma generada por finalizarPago
Route::get('/grid-actividades/inscripcion/{inscripcion}', [GridActividadesController::class, 'showPublic'])
    ->name('grid-actividades.inscripcion')
    ->middleware('signed');
Route::get('/membresias/public', [MembresiasController::class, 'publicIndex'])
    ->name('membresias.public.index');
Route::post('/membresias/public/subscribe', [MembresiasController::class, 'subscribePublic'])
    ->name('membresias.public.subscribe')
    ->middleware('throttle:public-write');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified',])->group(function () {
    // Previews de email con {id}: requieren auth + role (admin/editor).
    // El controller hace abort_unless adicional como defensa en profundidad.
    Route::get('/email-preview/inscripcion/{id}', [EmailPreviewController::class, 'inscripcionRegistrada'])
        ->name('preview.email.inscripcion.id');
    Route::get('/email-preview/inscripcion-confirmada/{id}', [EmailPreviewController::class, 'inscripcionConfirmada'])
        ->name('preview.email.inscripcion-confirmada.id');
    Route::get('/email-preview/grabacion/{id}', [EmailPreviewController::class, 'grabacionDisponible'])
        ->name('preview.email.grabacion.id');
    Route::get('/email-preview/informacion-membresias/{id}', [EmailPreviewController::class, 'informacionMembresias'])
        ->name('preview.email.informacion-membresias.id');
    Route::get('/email-preview/inscripcion-tk-registrada/{id}', [EmailPreviewController::class, 'inscripcionTkRegistrada'])
        ->name('preview.email.inscripcion-tk-registrada.id');
    Route::get('/email-preview/actividades-online/{id}', [EmailPreviewController::class, 'envioActividadesOnline'])
        ->name('preview.email.actividades-online.id');

    Route::get('/complete-profile', [ProfileCompletionController::class, 'create'])
        ->name('profile.complete');
    Route::post('/complete-profile', [ProfileCompletionController::class, 'store'])
        ->name('profile.complete.store');
    Route::get('/complete-profile/edit', [ProfileCompletionController::class, 'edit'])
        ->name('profile.complete.edit'); // Modo "editar" (updating=true)
    Route::put('/complete-profile', [ProfileCompletionController::class, 'update'])
        ->name('profile.complete.update');

    Route::get('/mi-camino-budista', fn () => inertia('MiCaminoBudista/Index'))
        ->name('camino-budista.index');

    Route::get('/area-estudio', [AreaEstudioController::class, 'index'])
        ->name('area-estudio.index');

    Route::put('/user/theme-preference', [ThemePreferenceController::class, 'update'])
        ->name('user.theme-preference.update');

    Route::get('/frases-de-dharma', [FrasesDeDharmaController::class, 'index'])
        ->name('frases-de-dharma.index');
    Route::post('/frases-de-dharma/import', [FrasesDeDharmaController::class, 'import'])
        ->name('frases-de-dharma.import');
    Route::delete('/frases-de-dharma/{fraseDeDharma}', [FrasesDeDharmaController::class, 'destroy'])
        ->name('frases-de-dharma.destroy');

    Route::get('/dashboard', function () {
        $user = auth()->user();
        // Chequeas si el perfil está incompleto
        if (!$user instanceof User) {
            return redirect()->route('login');
        }

        if (is_null($user->telefono)) {
            return redirect()->route('profile.complete');
        }

        if ($user->hasRole('asistant')) {
            return redirect()->route('asistant.panel');
        }

        $frase = \App\Models\FraseDeDharma::inRandomOrder()->first();

        return inertia('Dashboard', [
            'frase' => $frase ? [
                'cita_textual' => $frase->cita_textual,
                'libro' => $frase->libro,
            ] : null,
        ]);
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::get('/panel-asistant', function () {
        $frase = \App\Models\FraseDeDharma::inRandomOrder()->first();

        return inertia('Asistant/Panel', [
            'frase' => $frase ? [
                'cita_textual' => $frase->cita_textual,
                'libro' => $frase->libro,
            ] : null,
        ]);
    })->middleware(['auth', 'verified'])->name('asistant.panel');

    Route::resource('entidades', EntidadesController::class, [
        'parameters' => ['entidades' => 'entidad'], ]); // Renombrar el parámetro a 'entidad' por singular español
    Route::resource('lugares', LugaresController::class, [
        'parameters' => ['lugares' => 'lugar'],
    ]);
    Route::resource('/tiposactividad', TiposActividadController::class, [
        'parameters' => ['tiposactividad' => 'tipoactividad'],]);
    Route::resource('/disponibilidades', DisponibilidadesController::class, [
        'parameters' => ['disponibilidades' => 'disponibilidad'],]);
    Route::resource('/maestros', MaestrosController::class);
    Route::post('/coordinadores/importar-usuarios', [CoordinadoresController::class, 'importarDesdeUsuarios'])
        ->name('coordinadores.importar-usuarios');
    Route::resource('/coordinadores', CoordinadoresController::class , [
        'parameters' => ['coordinadores' => 'coordinador'],]);
    Route::resource('/monedas', MonedasController::class);
    Route::resource('/metodospago', MetodosPagoController::class, [
        'parameters' => ['metodospago' => 'metodopago'],]);
    Route::resource('/actividades', ActividadesController::class, [
        'parameters' => ['actividades' => 'actividad'],]);
    Route::patch('/actividades/{actividad}/estado', [ActividadesController::class, 'updateEstado'])
        ->name('actividades.updateEstado');
    Route::resource('/grid-actividades', GridActividadesController::class, [
        'parameters' => ['grid-actividades' => 'grid-actividad'],
    ])->except(['index']);
    // ----- Usuarios (administración) -----
    // Requieren permisos granulares de Spatie. Las rutas /complete-profile sin {user}
    // (líneas previas, sin {user}) son para el usuario sobre sí mismo y siguen sin permisos.
    Route::get('/usuarios/{user}/perfil', [UsuariosController::class, 'profileShow'])
        ->middleware('permission:read usuarios')
        ->name('usuarios.profile.show');
    Route::get('/usuarios/{user}/complete-profile/edit', [ProfileCompletionController::class, 'editUser'])
        ->middleware('permission:update usuarios')
        ->name('usuarios.profile.complete.edit');
    Route::put('/usuarios/{user}/complete-profile', [ProfileCompletionController::class, 'updateUser'])
        ->middleware('permission:update usuarios')
        ->name('usuarios.profile.complete.update');
    Route::get('/usuarios', [UsuariosController::class, 'index'])
        ->middleware('permission:read usuarios')
        ->name('usuarios.index');
    Route::get('/usuarios/create', [UsuariosController::class, 'create'])
        ->middleware('permission:create usuarios')
        ->name('usuarios.create');
    Route::post('/usuarios', [UsuariosController::class, 'store'])
        ->middleware('permission:create usuarios')
        ->name('usuarios.store');
    Route::get('/usuarios/{user}', [UsuariosController::class, 'show'])
        ->middleware('permission:read usuarios')
        ->name('usuarios.show');
    Route::get('/usuarios/{user}/edit', [UsuariosController::class, 'edit'])
        ->middleware('permission:update usuarios')
        ->name('usuarios.edit');
    Route::match(['put', 'patch'], '/usuarios/{user}', [UsuariosController::class, 'update'])
        ->middleware('permission:update usuarios')
        ->name('usuarios.update');
    Route::delete('/usuarios/{user}', [UsuariosController::class, 'destroy'])
        ->middleware('permission:delete usuarios')
        ->name('usuarios.destroy');
    Route::resource('/perfiles', PerfilesController::class);
    Route::resource('/roles', RolesController::class);
    Route::resource('/permisos', PermisosController::class, [
        'parameters' => ['permisos' => 'permiso'],
    ])->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('/emails', EmailPlantillasController::class);
    Route::get('/email-envio-configuraciones', [EmailEnvioConfiguracionesController::class, 'index'])
        ->name('email-envio-configuraciones.index');
    Route::put('/email-envio-configuraciones', [EmailEnvioConfiguracionesController::class, 'update'])
        ->name('email-envio-configuraciones.update');
    Route::get('/envio-correos', [EnvioCorreosController::class, 'index'])->name('envio-correos.index');
    Route::get('/envio-actividades-online', [EnvioActividadesOnlineController::class, 'index'])
        ->name('envio-actividades-online.index');
    Route::post('/envio-actividades-online/enviar', [EnvioActividadesOnlineController::class, 'enviar'])
        ->name('envio-actividades-online.enviar');
    Route::resource('/membresias', MembresiasController::class);
    Route::get('/membresias-gestion', [MembresiasController::class, 'gestion'])->name('membresias.gestion');
    Route::post('/membresias/subscribe', [MembresiasController::class, 'subscribe'])->name('membresias.subscribe');
    Route::get('/membresias-gestion-usuarios', [MembresiasGestionController::class, 'index'])
        ->middleware('permission:read membresias')
        ->name('membresias.gestion-usuarios');
    Route::put('/membresias-gestion-usuarios/{user}/asignar', [MembresiasGestionController::class, 'asignar'])
        ->middleware('permission:update membresias')
        ->name('membresias.asignar');
    Route::put('/membresias-gestion-usuarios/{user}/editar', [MembresiasGestionController::class, 'editar'])
        ->middleware('permission:update membresias')
        ->name('membresias.editar');
    Route::delete('/membresias-gestion-usuarios/{user}/eliminar', [MembresiasGestionController::class, 'eliminar'])
        ->middleware('permission:delete membresias')
        ->name('membresias.eliminar');
    Route::resource('/comidas', ComidasController::class);
    Route::resource('/libros', LibrosController::class)->except(['show']);
    Route::resource('/inventario-libros', InventarioLibrosController::class, [
        'parameters' => ['inventario-libros' => 'inventario_libro'],
    ])->except(['show']);
    Route::get('/inventario-libros/historico-pedidos', [HistoricoPedidosLibrosController::class, 'index'])
        ->name('inventario-libros.historico-pedidos');
    Route::get('/inventario-libros/por-entidad', [InventarioPorEntidadController::class, 'index'])
        ->name('inventario-libros.por-entidad');
    Route::get('/inventario-libros/ventas', [VentasLibrosController::class, 'index'])
        ->name('inventario-libros.ventas.index');
    Route::get('/inventario-libros/ventas/libros-por-entidad', [VentasLibrosController::class, 'librosPorEntidad'])
        ->name('inventario-libros.ventas.libros-por-entidad');
    Route::post('/inventario-libros/ventas', [VentasLibrosController::class, 'store'])
        ->name('inventario-libros.ventas.store');
    Route::get('/inventario-libros/prestamos-anexos', [PrestamosAnexosController::class, 'index'])
        ->name('inventario-libros.prestamos-anexos.index');
    Route::get('/inventario-libros/prestamos-anexos/create', [PrestamosAnexosController::class, 'create'])
        ->name('inventario-libros.prestamos-anexos.create');
    Route::post('/inventario-libros/prestamos-anexos', [PrestamosAnexosController::class, 'store'])
        ->name('inventario-libros.prestamos-anexos.store');
    Route::get('/inventario-libros/prestamos-anexos/{prestamo_anexo}/edit', [PrestamosAnexosController::class, 'edit'])
        ->name('inventario-libros.prestamos-anexos.edit');
    Route::put('/inventario-libros/prestamos-anexos/{prestamo_anexo}', [PrestamosAnexosController::class, 'update'])
        ->name('inventario-libros.prestamos-anexos.update');
    Route::delete('/inventario-libros/prestamos-anexos/{prestamo_anexo}', [PrestamosAnexosController::class, 'destroy'])
        ->name('inventario-libros.prestamos-anexos.destroy');
    Route::get('/inventario-libros/devoluciones-anexos', [DevolucionesAnexosController::class, 'index'])
        ->name('inventario-libros.devoluciones-anexos.index');
    Route::get('/inventario-libros/devoluciones-anexos/create', [DevolucionesAnexosController::class, 'create'])
        ->name('inventario-libros.devoluciones-anexos.create');
    Route::get('/inventario-libros/devoluciones-anexos/libros-por-entidad', [DevolucionesAnexosController::class, 'librosPorEntidad'])
        ->name('inventario-libros.devoluciones-anexos.libros-por-entidad');
    Route::post('/inventario-libros/devoluciones-anexos', [DevolucionesAnexosController::class, 'store'])
        ->name('inventario-libros.devoluciones-anexos.store');
    Route::resource('/hospedajes', HospedajesController::class);
    Route::resource('/transportes', TransportesController::class);
    Route::resource('/inscripciones', InscripcionesController::class, [
        'parameters' => ['inscripciones' => 'inscripcion'],]);
    Route::get('/inscripciones-clases/buscar-usuario', [InscripcionesClasesController::class, 'buscarUsuario'])
        ->name('inscripciones-clases.buscar-usuario');
    Route::get('/inscripciones-clases/precios-clase', [InscripcionesClasesController::class, 'preciosPorClase'])
        ->name('inscripciones-clases.precios-clase');
    Route::get('/inscripciones-clases/libros-por-entidad', [InscripcionesClasesController::class, 'librosPorEntidad'])
        ->name('inscripciones-clases.libros-por-entidad');
    Route::resource('/inscripciones-clases', InscripcionesClasesController::class, [
        'parameters' => ['inscripciones-clases' => 'inscripciones_clase'],
    ])->except(['show']);
    Route::get('/inscripciones-por-actividad', [InscripcionesController::class, 'porActividad'])
        ->name('inscripciones.por-actividad');
    Route::post('/inscripciones/{inscripcion}/comprobante', [InscripcionesController::class, 'uploadComprobante'])
        ->name('inscripciones.comprobante');
    Route::post('/inscripciones/{inscripcion}/pago/prepare', [InscripcionesController::class, 'preparePago'])
        ->name('inscripciones.pago.prepare');
    Route::get('/inscripciones/{inscripcion}/ticket', [InscripcionesController::class, 'ticket'])
        ->name('inscripciones.ticket');
    Route::get('/inscripciones/{inscripcion}/asistir', [InscripcionesController::class, 'asistir'])
        ->name('inscripciones.asistir')->middleware('signed');
    Route::get('/inscripciones/{inscripcion}/ticket-qr', [InscripcionesController::class, 'ticketQr'])
        ->name('inscripciones.ticketQr');
    // Importación de inscripciones legacy (CSV). Antes del resource para que
    // GET /estadoinscripciones/importar no lo capture la ruta show {estadoinscripcion}.
    Route::get('/estadoinscripciones/importar', [ImportarInscripcionesController::class, 'index'])
        ->name('estadoinscripciones.importar');
    Route::post('/estadoinscripciones/importar/preview', [ImportarInscripcionesController::class, 'preview'])
        ->name('estadoinscripciones.importar.preview');
    Route::post('/estadoinscripciones/importar/confirmar', [ImportarInscripcionesController::class, 'store'])
        ->name('estadoinscripciones.importar.confirmar');
    Route::get('/estadoinscripciones/importar/reportes/{archivo}', [ImportarInscripcionesController::class, 'descargarReporte'])
        ->where('archivo', '[\w\-.]+')
        ->name('estadoinscripciones.importar.reporte.descargar');
    Route::delete('/estadoinscripciones/importar/reportes/{archivo}', [ImportarInscripcionesController::class, 'eliminarReporte'])
        ->where('archivo', '[\w\-.]+')
        ->name('estadoinscripciones.importar.reporte.eliminar');
    Route::get('/estadoinscripciones/{estadoinscripcion}/editar-data', [EstadoInscripcionesController::class, 'editarData'])
        ->name('estadoinscripciones.editar-data');
    Route::patch('/estadoinscripciones/{estadoinscripcion}/pago', [EstadoInscripcionesController::class, 'marcarPago'])
        ->name('estadoinscripciones.pago');
    // Admin inscribe en nombre de otra persona: prepara la sesión y luego usa la pantalla de pago.
    Route::post('/estadoinscripciones/crear/prepare', [EstadoInscripcionesController::class, 'crearInscripcionPrepare'])
        ->name('estadoinscripciones.crear-prepare');
    Route::get('/estadoinscripciones/usuarios/buscar', [EstadoInscripcionesController::class, 'buscarUsuarios'])
        ->name('estadoinscripciones.buscar-usuarios');
    Route::resource('/estadoinscripciones', EstadoInscripcionesController::class, [
        'parameters' => ['estadoinscripciones' => 'estadoinscripcion'],]);
    Route::get('/estadoinscripciones/confirmaciones/count', [EstadoInscripcionesController::class, 'countConfirmacionesPendientes'])
        ->name('estadoinscripciones.confirmaciones.count');
    Route::post('/estadoinscripciones/confirmaciones/enviar', [EstadoInscripcionesController::class, 'enviarConfirmacionesPendientes'])
        ->name('estadoinscripciones.confirmaciones.enviar');
    Route::get('/estadoinscripciones/grabaciones/count', [EstadoInscripcionesController::class, 'countGrabacionesPendientes'])
        ->name('estadoinscripciones.grabaciones.count');
    Route::post('/estadoinscripciones/grabaciones/enviar', [EstadoInscripcionesController::class, 'enviarGrabacionesPendientes'])
        ->name('estadoinscripciones.grabaciones.enviar');
    Route::patch('/invitados/{invitado}/asistencia', [InvitadosController::class, 'asistencia'])
        ->name('invitados.asistencia');
    Route::resource('/lugareshospedaje', LugaresHospedajeController::class, [
        'parameters' => ['lugareshospedaje' => 'lugarhospedaje'],]);
    Route::resource('/modalidades', ModalidadesController::class, [
        'parameters' => ['modalidades' => 'modalidad'],]);
    Route::resource('/centroayuda', CentroAyudaController::class);
    Route::resource('/tutoriales', TutorialesController::class, [
        'parameters' => ['tutoriales' => 'tutorial'],
    ])->only(['index', 'store', 'update', 'destroy'])
      ->middleware('role:admin|editor');
    Route::get('/novedades/gestion', [NovedadesController::class, 'gestion'])->name('novedades.gestion');
    Route::resource('/novedades', NovedadesController::class, [
        'parameters' => ['novedades' => 'novedad'],]);
    Route::resource('/reporteerror', ReporteErrorController::class);
    Route::resource('/acercade', AcercaDeController::class);
    Route::resource('/versiones', VersionesController::class, [
        'parameters' => ['versiones' => 'version'],]);
    Route::put('/tickets/asignar/{ticket}', [TicketsController::class, 'asignar'])->name('tickets.asignar');
    Route::resource('/descripciones', DescripcionesController::class, [
        'parameters' => ['descripciones' => 'descripcion'],]);
    Route::resource('/programas', ProgramasController::class);

    // Grabaciones MP3 por Programa de Estudio.
    Route::get('/programa-grabaciones', [ProgramaGrabacionController::class, 'index'])
        ->middleware('permission:read programa-grabaciones')
        ->name('programa-grabaciones.index');
    Route::post('/programa-grabaciones', [ProgramaGrabacionController::class, 'store'])
        ->middleware('permission:create programa-grabaciones')
        ->name('programa-grabaciones.store');
    Route::delete('/programa-grabaciones/{programaGrabacion}', [ProgramaGrabacionController::class, 'destroy'])
        ->middleware('permission:delete programa-grabaciones')
        ->name('programa-grabaciones.destroy');

    // Asignacion de programa de estudio a usuarios (debe ir ANTES del resource para evitar conflicto con {programaEstudio}).
    Route::get('/programa-estudios/asignacion-usuarios', [ProgramaEstudiosController::class, 'asignacionUsuarios'])
        ->middleware('permission:update programa-estudios')
        ->name('programa-estudios.asignacion-usuarios');
    Route::patch('/programa-estudios/asignacion-usuarios/{user}', [ProgramaEstudiosController::class, 'actualizarAsignacionUsuario'])
        ->middleware('permission:update programa-estudios')
        ->name('programa-estudios.asignacion-usuarios.update');

    Route::resource('/programa-estudios', ProgramaEstudiosController::class, [
        'parameters' => ['programa-estudios' => 'programaEstudio'],
    ]);
    Route::resource('/oracionescantadas', OracionesCantadasController::class, [
        'parameters' => ['oracionescantadas' => 'oracionCantada'],
    ]);
    Route::resource('/paginas-actividades-online', PaginasActividadesOnlineController::class, [
        'parameters' => ['paginas-actividades-online' => 'paginas_actividades_online'],
    ]);
    Route::get('/paginas/configuracion', [PaginasConfiguracionController::class, 'index'])
        ->middleware('permission:read paginasconfiguracion')
        ->name('paginas.configuracion');
    Route::put('/paginas/configuracion', [PaginasConfiguracionController::class, 'update'])
        ->middleware('permission:update paginasconfiguracion')
        ->name('paginas.configuracion.update');
    Route::get('/configuracion/mail-info-membresias', [MailInfoMembresiasController::class, 'index'])
        ->name('mail-info-membresias.index');
    Route::put('/configuracion/mail-info-membresias', [MailInfoMembresiasController::class, 'update'])
        ->name('mail-info-membresias.update');

    // Importación de membresías desde CSV (solo admin).
    Route::middleware('role:admin')->group(function () {
        Route::get('/configuracion/importar-membresias', [ImportarMembresiasController::class, 'index'])
            ->name('configuracion.importar-membresias');
        Route::post('/configuracion/importar-membresias/preview', [ImportarMembresiasController::class, 'preview'])
            ->name('configuracion.importar-membresias.preview');
        Route::post('/configuracion/importar-membresias/confirmar', [ImportarMembresiasController::class, 'store'])
            ->name('configuracion.importar-membresias.confirmar');
    });
    Route::resource('/ciclos', CiclosController::class, [
        'parameters' => ['ciclos' => 'ciclo'],
    ]);
    Route::resource('/clases', ClasesController::class, [
        'parameters' => ['clases' => 'clase'],
    ]);
    Route::resource('/asistencias', AsistenciasController::class, [
        'parameters' => ['asistencias' => 'asistencia'],
    ])->only(['index']);
    Route::post('/asistencias/registrar-qr', [AsistenciasController::class, 'registrarDesdeQr'])
        ->name('asistencias.registrar-qr');
    Route::patch('/clases/{clase}/estado', [ClasesController::class, 'updateEstado'])
        ->name('clases.updateEstado');
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

    Route::resource('/precio-grupos', PrecioGruposController::class)->except(['show']);
    Route::post('/precio-grupos/{id}/membresias', [PrecioGruposController::class, 'storeMembresia'])
        ->name('precio-grupos.storeMembresia');
    Route::put('/precio-grupos/membresias/{lineaId}', [PrecioGruposController::class, 'updateMembresia'])
        ->name('precio-grupos.updateMembresia');
    Route::delete('/precio-grupos/membresias/{lineaId}', [PrecioGruposController::class, 'destroyMembresia'])
        ->name('precio-grupos.destroyMembresia');
    Route::post('/precio-grupos/{precioGrupo}/aplicar', [PrecioGruposController::class, 'aplicar'])
        ->middleware('permission:update precio-grupos')
        ->name('precio-grupos.aplicar');

    Route::resource('/streams', StreamsController::class);
    Route::post('/streams/{id}/links', [StreamsController::class, 'storeLink'])
    ->name('streams.storeLink');
    Route::put('/streams/links/{linkLineId}', [StreamsController::class, 'updateLink'])
    ->name('streams.updateLink');
    Route::delete('/streams/links/{linkLineId}', [StreamsController::class, 'destroyLink'])
    ->name('streams.destroyLink');

    Route::resource('/grabaciones', GrabacionesController::class, [
        'parameters' => ['grabaciones' => 'grabacion'],]);
    // ----- Botones de pago -----
    Route::get('/botonespago', [BotonesPagoController::class, 'index'])
        ->middleware('permission:read botonpago')
        ->name('botonespago.index');
    Route::get('/botonespago/create', [BotonesPagoController::class, 'create'])
        ->middleware('permission:create botonpago')
        ->name('botonespago.create');
    Route::post('/botonespago', [BotonesPagoController::class, 'store'])
        ->middleware('permission:create botonpago')
        ->name('botonespago.store');
    Route::get('/botonespago/{botonpago}', [BotonesPagoController::class, 'show'])
        ->middleware('permission:read botonpago')
        ->name('botonespago.show');
    Route::get('/botonespago/{botonpago}/edit', [BotonesPagoController::class, 'edit'])
        ->middleware('permission:update botonpago')
        ->name('botonespago.edit');
    Route::match(['put', 'patch'], '/botonespago/{botonpago}', [BotonesPagoController::class, 'update'])
        ->middleware('permission:update botonpago')
        ->name('botonespago.update');
    Route::delete('/botonespago/{botonpago}', [BotonesPagoController::class, 'destroy'])
        ->middleware('permission:delete botonpago')
        ->name('botonespago.destroy');

    // ----- Exención de pago -----
    Route::get('/excencionpago', [ExcencionPagoController::class, 'index'])
        ->middleware('permission:read excencionpago')
        ->name('excencionpago.index');
    Route::get('/excencionpago/create', [ExcencionPagoController::class, 'create'])
        ->middleware('permission:create excencionpago')
        ->name('excencionpago.create');
    Route::post('/excencionpago', [ExcencionPagoController::class, 'store'])
        ->middleware('permission:create excencionpago')
        ->name('excencionpago.store');
    Route::get('/excencionpago/{excencionpago}', [ExcencionPagoController::class, 'show'])
        ->middleware('permission:read excencionpago')
        ->name('excencionpago.show');
    Route::get('/excencionpago/{excencionpago}/edit', [ExcencionPagoController::class, 'edit'])
        ->middleware('permission:update excencionpago')
        ->name('excencionpago.edit');
    Route::match(['put', 'patch'], '/excencionpago/{excencionpago}', [ExcencionPagoController::class, 'update'])
        ->middleware('permission:update excencionpago')
        ->name('excencionpago.update');
    Route::delete('/excencionpago/{excencionpago}', [ExcencionPagoController::class, 'destroy'])
        ->middleware('permission:delete excencionpago')
        ->name('excencionpago.destroy');
    Route::get('/grabaciones/{grabacion}/links', [GrabacionesController::class, 'editLinks'])
    ->name('grabaciones.links');
    Route::post('/grabaciones/{id}/links', [GrabacionesController::class, 'storeLink'])
    ->name('grabaciones.storeLink');
    Route::put('/grabaciones/links/{linkLineId}', [GrabacionesController::class, 'updateLink'])
    ->name('grabaciones.updateLink');
    Route::delete('/grabaciones/links/{linkLineId}', [GrabacionesController::class, 'destroyLink'])
    ->name('grabaciones.destroyLink');
    
    Route::resource('/registromembresias', RegistroMembresiasController::class);

    // ----- Estado de cuenta de membresías (vista administrativa) -----
    Route::get('/estado-cuenta-membresias', [EstadoCuentaMembresiasController::class, 'index'])
        ->middleware('permission:read estado_cuenta_membresias')
        ->name('estado-cuenta-membresias.index');
    Route::get('/estado-cuenta-membresias/{estadoCuentaMembresia}', [EstadoCuentaMembresiasController::class, 'show'])
        ->middleware('permission:read estado_cuenta_membresias')
        ->name('estado-cuenta-membresias.show');
    Route::get('/estado-cuenta-membresias/{estadoCuentaMembresia}/edit', [EstadoCuentaMembresiasController::class, 'edit'])
        ->middleware('permission:update estado_cuenta_membresias')
        ->name('estado-cuenta-membresias.edit');
    Route::match(['put', 'patch'], '/estado-cuenta-membresias/{estadoCuentaMembresia}', [EstadoCuentaMembresiasController::class, 'update'])
        ->middleware('permission:update estado_cuenta_membresias')
        ->name('estado-cuenta-membresias.update');
    Route::delete('/estado-cuenta-membresias/{estadoCuentaMembresia}', [EstadoCuentaMembresiasController::class, 'destroy'])
        ->middleware('permission:delete estado_cuenta_membresias')
        ->name('estado-cuenta-membresias.destroy');
    // uploadComprobante NO lleva permission: es para que el propio usuario suba su
    // comprobante de membresía. El controller filtra internamente por $request->user()->id.
    Route::post('/estado-cuenta-membresias/comprobante', [EstadoCuentaMembresiasController::class, 'uploadComprobante'])
        ->name('estado-cuenta-membresias.comprobante');
    Route::post('/estado-cuenta-membresias/generar', [EstadoCuentaMembresiasController::class, 'generar'])
        ->middleware('permission:update estado_cuenta_membresias')
        ->name('estado-cuenta-membresias.generar');
    Route::post('/estado-cuenta-membresias/revertir', [EstadoCuentaMembresiasController::class, 'revertir'])
        ->middleware('permission:update estado_cuenta_membresias')
        ->name('estado-cuenta-membresias.revertir');

    Route::resource('/imagenes', ImagenesController::class, [
        'parameters' => ['imagenes' => 'imagen'],]);

    Route::post('/imagenes-json', [ImagenesController::class, 'storeJson'])->name('imagenes.json');

});
