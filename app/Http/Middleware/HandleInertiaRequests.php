<?php

namespace App\Http\Middleware;

use App\Http\Controllers\VerComoUsuarioController;
use App\Models\ConfiguracionSistema;
use App\Models\Entidad;
use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Support\Header;
use Spatie\Permission\Models\Role;
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

        // Inertia sólo *lee* `X-Inertia-Version` de la petición: no lo emite en
        // ninguna respuesta, ni siquiera en el 409 de version mismatch, que además
        // viene con el cuerpo vacío. Sin esto, un chequeo externo que quiera pedir la
        // variante JSON no tiene de dónde sacar la versión salvo scrapeando el
        // `data-page` del HTML; sin versión válida sólo recibe ese 409, se queda sin
        // JSON que mirar y no puede verificar los headers de caché de arriba.
        // Declararlo no cambia nada para el cliente —el router lee la versión del
        // `data-page` y nunca mira este header— y no expone nada que no esté ya en
        // el HTML.
        if ($version = $this->version($request)) {
            $response->headers->set(Header::VERSION, $version);
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
        $user = $request->user();
        $puedeSimular = $user && ! $user->hasRole('asistant');
        $simulando = $puedeSimular
            && $request->hasSession()
            && $request->session()->get(VerComoUsuarioController::SESSION_KEY);

        // "Ver como usuario": todo el frontend decide qué mostrar con estos dos
        // props, así que mientras dura la simulación se comparten los del rol
        // asistant en lugar de los reales. Es solo visual: los permisos del
        // server siguen siendo los del usuario.
        if ($simulando) {
            $roles = collect(['asistant']);
            $permissions = Role::where('name', 'asistant')->first()?->permissions->pluck('name') ?? collect();
        } else {
            $roles = $user ? $user->roles->pluck('name') : [];
            $permissions = $user ? $user->getPermissionsViaRoles()->pluck('name') : [];
        }

        return array_merge(parent::share($request), [
            'user.roles' => $roles,
            'user.permissions' => $permissions,
            'simulacion' => [
                'activa' => (bool) $simulando,
                'disponible' => (bool) $puedeSimular,
            ],
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
