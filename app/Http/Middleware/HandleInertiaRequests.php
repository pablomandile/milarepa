<?php

namespace App\Http\Middleware;

use App\Models\ConfiguracionSistema;
use App\Models\Entidad;
use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;

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
     * Evita que el navegador guarde la respuesta JSON de Inertia bajo la misma
     * clave de caché que el HTML de la página.
     *
     * Una URL de Inertia devuelve dos cuerpos según el header `X-Inertia`: el HTML
     * de arranque para una navegación, el JSON de la página para un XHR. Lo único
     * que se lo dice a una caché es `Vary: X-Inertia`, y el CDN de Hostinger
     * (`server: hcdn`) lo reescribe — cuando comprime con brotli, que es lo que
     * pide cualquier navegador real, lo borra entero. Con la URL pelada como clave,
     * el JSON de una navegación interna pisa al HTML; Chrome descarta la pestaña
     * inactiva y al restaurarla reusa esa entrada, así que el usuario ve el JSON
     * crudo en pantalla y F5 lo "arregla".
     *
     * Va acá y no en un middleware aparte: el middleware de Inertia setea el `Vary`
     * y puede reemplazar la respuesta entera en `onVersionChange()`, así que uno
     * agregado después en el grupo `web` correría su salida antes y quedaría pisado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = parent::handle($request, $next);

        // El CDN lo borra, pero se declara igual: es lo correcto y sirve en
        // cualquier intermediario que sí lo respete.
        $response->headers->set('Vary', Header::INERTIA.', Accept-Encoding');

        // `no-store` y no `no-cache`: `no-cache` permite guardar y sólo obliga a
        // revalidar, y restaurar una pestaña descartada es una navegación de
        // historial, que saltea la revalidación (el mismo motivo por el que el
        // botón "atrás" muestra contenido viejo pese a `no-cache`).
        //
        // Sólo sobre la respuesta XHR, NUNCA sobre el HTML: `no-store` en el
        // documento principal desactiva el back/forward cache de Chrome y convierte
        // cada "atrás" en una ida completa a la red, sin ningún síntoma que lo
        // delate. Hay un test que lo cuida.
        if ($request->header(Header::INERTIA)) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

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
            'auth.theme_preference' => fn () => $request->user()?->theme_preference,
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
