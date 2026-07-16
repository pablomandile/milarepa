<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use App\Models\Entidad;
use App\Models\Version;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // morphMap (no enforce): fija alias cortos para el ledger de cobros sin obligar
        // al resto de los modelos polimórficos (p. ej. User en Spatie) a estar en el mapa.
        Relation::morphMap([
            'inscripcion' => \App\Models\Inscripcion::class,
            'inscripcion_clase' => \App\Models\InscripcionClase::class,
            'membresia_cuota' => \App\Models\EstadoCuentaMembresia::class,
            'venta' => \App\Models\Venta::class,
            // Catálogos Tharpa (usados como `vendible`/`producto` en POS e inscripciones).
            'libro' => \App\Models\Libro::class,
            'oracion' => \App\Models\Oracion::class,
            'arte' => \App\Models\Arte::class,
            'otro' => \App\Models\Otro::class,
            // Tienda general (usado como `vendible` en el POS).
            'articulo_tienda' => \App\Models\ArticuloTienda::class,
            // POS.
            'venta_pos' => \App\Models\VentaPos::class,
            'venta_pos_item' => \App\Models\VentaPosItem::class,
        ]);

        Inertia::share([
            'entidad_principal' => function () {
                $entidadPrincipal = Entidad::where('entidad_principal', true)->first();
                return $entidadPrincipal ? $entidadPrincipal->toArray() : null;
            },
            'version' => function () {
                $version = Version::latest()->first();
                return [
                    'version' => $version?->version,
                    'created_at' => $version && $version->created_at
                        ? Carbon::parse($version->created_at)->translatedFormat('F Y')
                        : null
                ];
            }
        ]);
    }
}
