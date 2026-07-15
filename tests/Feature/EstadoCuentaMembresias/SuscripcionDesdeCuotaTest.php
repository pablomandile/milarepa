<?php

namespace Tests\Feature\EstadoCuentaMembresias;

use App\Models\EstadoCuentaMembresia;
use App\Models\Membresia;
use App\Models\MembresiaUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Al editar una cuota de membresía y elegir modo "Suscripción", se activa
 * `membresia_usuario.suscripcion` (para que el generador mensual dé por saldado
 * el próximo mes). Es one-way: ningún otro modo la desactiva, y editar la cuota
 * preserva el resto de los campos del perfil.
 */
class SuscripcionDesdeCuotaTest extends TestCase
{
    use DatabaseTransactions;

    /** users.pais_id es NOT NULL DEFAULT 1 con FK a paises. */
    private function asegurarPaisDefault(): void
    {
        DB::statement(
            'INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())',
            ['Argentina'],
        );
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function membresia(): Membresia
    {
        return Membresia::create(['nombre' => 'TK Test', 'descripcion' => 'Plan', 'valor' => 5200]);
    }

    private function cuota(User $user, Membresia $membresia, string $modo = 'Transferencia'): EstadoCuentaMembresia
    {
        return EstadoCuentaMembresia::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'mes_pagado' => '2026-07',
            'importe' => 5200,
            'pagado' => false,
            'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
            'modo' => $modo,
            'observaciones' => '',
        ]);
    }

    /** @test */
    public function marcar_cuota_como_suscripcion_activa_la_suscripcion_del_perfil(): void
    {
        $this->asegurarPaisDefault();
        $user = User::factory()->create(); // sin perfil de membresía todavía
        $membresia = $this->membresia();
        $cuota = $this->cuota($user, $membresia);

        $this->actingAs($this->admin())
            ->put(route('estado-cuenta-membresias.update', $cuota->id), [
                'pagado' => true,
                'estado_activo' => true,
                'modo' => 'Suscripción',
            ])
            ->assertRedirect(route('estado-cuenta-membresias.index'));

        // Se crea el perfil con la suscripción activa y la membresía alineada con la cuota.
        $this->assertDatabaseHas('membresia_usuario', [
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'suscripcion' => 1,
        ]);
    }

    /** @test */
    public function guardar_con_otro_modo_no_desactiva_la_suscripcion(): void
    {
        $this->asegurarPaisDefault();
        $user = User::factory()->create();
        $membresia = $this->membresia();

        MembresiaUsuario::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'suscripcion' => true,
            'membresia_online' => false,
            'info_tarjetas_kadampa' => false,
        ]);

        $cuota = $this->cuota($user, $membresia);

        $this->actingAs($this->admin())
            ->put(route('estado-cuenta-membresias.update', $cuota->id), [
                'pagado' => true,
                'estado_activo' => true,
                'modo' => 'Efectivo',
            ])
            ->assertRedirect(route('estado-cuenta-membresias.index'));

        // One-way: un pago suelto en otro modo NO da de baja la suscripción.
        $this->assertDatabaseHas('membresia_usuario', [
            'user_id' => $user->id,
            'suscripcion' => 1,
        ]);
    }

    /** @test */
    public function editar_modalidad_preserva_suscripcion_y_envio_act_online(): void
    {
        $this->asegurarPaisDefault();
        $user = User::factory()->create();
        $membresia = $this->membresia();

        MembresiaUsuario::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'suscripcion' => true,
            'membresia_online' => false,
            'info_tarjetas_kadampa' => false,
            'envioActOnline' => '2026-05-01',
        ]);

        $cuota = $this->cuota($user, $membresia);

        // Cambiar solo la modalidad (modo distinto de Suscripción) no debe pisar
        // `suscripcion` ni `envioActOnline` a sus defaults (regresión del bug latente).
        $this->actingAs($this->admin())
            ->put(route('estado-cuenta-membresias.update', $cuota->id), [
                'pagado' => true,
                'estado_activo' => true,
                'modo' => 'Efectivo',
                'modalidad' => 'online',
            ])
            ->assertRedirect(route('estado-cuenta-membresias.index'));

        $this->assertDatabaseHas('membresia_usuario', [
            'user_id' => $user->id,
            'suscripcion' => 1,
            'membresia_online' => 1,
            'envioActOnline' => '2026-05-01',
        ]);
    }
}
