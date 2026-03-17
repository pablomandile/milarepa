<?php

namespace Tests\Feature;

use App\Models\EstadoCuentaMembresia;
use App\Models\Membresia;
use App\Models\MembresiaUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RenovarMembresiasMensualTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function renueva_al_nuevo_mes_expirando_anterior_y_creando_actual_pendiente_si_no_es_suscripcion(): void
    {
        $user = User::factory()->create();
        $membresia = Membresia::create([
            'nombre' => 'TK Mensual',
            'descripcion' => 'Plan mensual',
            'valor' => 4500,
        ]);

        MembresiaUsuario::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'membresia_inscripcion_fecha' => '2026-02-01',
            'membresia_online' => false,
            'info_tarjetas_kadampa' => false,
        ]);

        $anterior = EstadoCuentaMembresia::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'mes_pagado' => '2026-02',
            'fecha_pago' => null,
            'importe' => 4500,
            'pagado' => false,
            'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
            'modo' => 'Transferencia',
            'info_pago' => 'CBU ejemplo',
            'observaciones' => 'Estado anterior',
        ]);

        Artisan::call('membresias:renovar-mensual', ['--fecha' => '2026-03-15']);

        $anterior->refresh();
        $this->assertSame(EstadoCuentaMembresia::ESTADO_EXPIRADA, $anterior->estado);

        $actual = EstadoCuentaMembresia::query()
            ->where('user_id', $user->id)
            ->where('membresia_id', $membresia->id)
            ->where('mes_pagado', '2026-03')
            ->first();

        $this->assertNotNull($actual);
        $this->assertSame(EstadoCuentaMembresia::ESTADO_ACTIVA, $actual->estado);
        $this->assertFalse((bool) $actual->pagado);
        $this->assertNull($actual->fecha_pago);
        $this->assertSame('Transferencia', $actual->modo);
        $this->assertSame('CBU ejemplo', $actual->info_pago);
    }

    /** @test */
    public function si_el_modo_es_suscripcion_el_nuevo_mes_se_crea_pagado(): void
    {
        $user = User::factory()->create();
        $membresia = Membresia::create([
            'nombre' => 'TK Suscripción',
            'descripcion' => 'Plan suscripción',
            'valor' => 5200,
        ]);

        MembresiaUsuario::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'membresia_inscripcion_fecha' => '2026-02-01',
            'membresia_online' => true,
            'info_tarjetas_kadampa' => false,
        ]);

        EstadoCuentaMembresia::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'mes_pagado' => '2026-02',
            'fecha_pago' => '2026-02-01',
            'importe' => 5200,
            'pagado' => true,
            'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
            'modo' => 'Suscripción',
            'info_pago' => 'Débito automático',
            'observaciones' => 'Estado anterior suscripción',
        ]);

        Artisan::call('membresias:renovar-mensual', ['--fecha' => '2026-03-15']);

        $actual = EstadoCuentaMembresia::query()
            ->where('user_id', $user->id)
            ->where('membresia_id', $membresia->id)
            ->where('mes_pagado', '2026-03')
            ->first();

        $this->assertNotNull($actual);
        $this->assertSame(EstadoCuentaMembresia::ESTADO_ACTIVA, $actual->estado);
        $this->assertTrue((bool) $actual->pagado);
        $this->assertNotNull($actual->fecha_pago);
        $this->assertSame('Suscripción', $actual->modo);

        $activas = EstadoCuentaMembresia::query()
            ->where('user_id', $user->id)
            ->where('estado', EstadoCuentaMembresia::ESTADO_ACTIVA)
            ->count();

        $this->assertSame(1, $activas);
    }
}
