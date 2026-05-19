<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Verifica que /grid-actividades/inscripcion/{inscripcion} (tarea 1.3)
 * tiene el middleware `signed` registrado.
 *
 * No usamos HTTP test directo porque Route Model Binding resuelve
 * {inscripcion} antes que los middlewares de ruta, así que sin una
 * inscripción existente recibimos 404 antes que 403. Verificar el
 * middleware en la definición de ruta es más directo y trazable.
 *
 * Vector original: anónimo podía hacer GET con cualquier id y ver datos
 * de inscripciones ajenas + comprobantes.
 */
class GridInscripcionSignedTest extends TestCase
{
    public function test_route_has_signed_middleware(): void
    {
        $route = Route::getRoutes()->getByName('grid-actividades.inscripcion');

        $this->assertNotNull($route, 'La ruta grid-actividades.inscripcion debe existir.');
        $this->assertContains(
            'signed',
            $route->middleware(),
            'La ruta grid-actividades.inscripcion debe tener el middleware `signed` para evitar IDOR.'
        );
    }
}
