<?php

namespace App\Providers;

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
        Inertia::share([
            'entidad_principal' => function () {
                $entidadPrincipal = Entidad::where('entidad_principal', true)->first();
                return $entidadPrincipal ? $entidadPrincipal->toArray() : null;
            },
            'version' => function () {
                $version = Version::latest()->first();
                return [
                    'version' => $version->version,
                    'created_at' => $version->created_at
                        ? Carbon::parse($version->created_at)->translatedFormat('F Y')
                        : null
                ];
            }
        ]);
    }
}
