<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use App\Models\Entidad;

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
        Inertia::share('entidad_principal', function () {
            // Obtener la entidad principal
            $entidadPrincipal = Entidad::where('entidad_principal', true)->first();
            return $entidadPrincipal ? $entidadPrincipal->toArray() : null;
        });
    }
}
