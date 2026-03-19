<?php

namespace App\Http\Middleware;

use App\Models\ConfiguracionSistema;
use App\Models\Entidad;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'user.roles' => $request->user() ? $request->user()->roles->pluck('name') : [],
            'user.permissions' => $request->user() ? $request->user()->getPermissionsViaRoles()->pluck('name') : [],
            'ui' => fn () => $this->resolverUiConfig(),
            'flash' => [
                // Mensajes tipo flash disponibles en el cliente
                'success' => fn () => session('success'),
                'error' => fn () => session('error'),
            ],
        ]); 
    }

    private function resolverUiConfig(): array
    {
        $mostrarLogoEntidadPrincipalNav = ConfiguracionSistema::obtenerBoolean('mostrar_logo_entidad_principal_nav', false);
        $mostrarLogoEntidadPrincipalFooter = ConfiguracionSistema::obtenerBoolean('mostrar_logo_entidad_principal_footer', false);
        $logoEntidadPrincipal = null;

        if ($mostrarLogoEntidadPrincipalNav || $mostrarLogoEntidadPrincipalFooter) {
            $entidadPrincipal = Entidad::query()
                ->where('entidad_principal', true)
                ->first();

            $logoEntidadPrincipal = $entidadPrincipal?->logo_url;
        }

        return [
            'mostrar_logo_entidad_principal_nav' => $mostrarLogoEntidadPrincipalNav,
            'mostrar_logo_entidad_principal_footer' => $mostrarLogoEntidadPrincipalFooter,
            'navbar_logo_url' => $mostrarLogoEntidadPrincipalNav ? $logoEntidadPrincipal : null,
            'footer_logo_url' => $mostrarLogoEntidadPrincipalFooter ? $logoEntidadPrincipal : null,
        ];
    }
}
