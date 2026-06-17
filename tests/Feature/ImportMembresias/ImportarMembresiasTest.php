<?php

namespace Tests\Feature\ImportMembresias;

use App\Models\Entidad;
use App\Models\EstadoCuentaMembresia;
use App\Models\Membresia;
use App\Models\User;
use App\Services\ImportarMembresiasService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Importación de membresías desde CSV.
 * Regresión: algunos exports (Google Sheets) ponen una primera fila de solo
 * delimitadores (",,,,,") antes del encabezado real. El parser debe ignorarla
 * y tomar como header la primera línea con contenido, mapeando la columna "Mail".
 */
class ImportarMembresiasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_preview_ignora_fila_inicial_de_solo_comas_y_mapea_mail(): void
    {
        $entidad = Entidad::create(['nombre' => 'Entidad Memb Test', 'abreviacion' => 'EMT', 'entidad_principal' => false]);

        $contenido = implode("\n", [
            ',,,,,,,,,',
            'Nombre,Apellido,Activo,TK,Programa,ONLINE,Mail,Teléfonos,Ciudad,Newsletter',
            'Ada,Spinacci,A,CLA,PG,SI,ada@example.com,123,CABA,Si',
            'Adri,Cubillan,A,COR,PFM,NO,adri@example.com,456,CABA,No',
        ]);
        $csv = UploadedFile::fake()->createWithContent('membresias.csv', $contenido);

        $preview = app(ImportarMembresiasService::class)->previsualizar($csv, $entidad->id);

        // Solo las 2 filas de datos (la fila de comas no cuenta como dato ni como header).
        $this->assertSame(2, $preview['total_filas']);
        $this->assertSame(0, $preview['errores']);
        $this->assertSame(2, $preview['filas_validas']);
        // La columna "Mail" se mapeó correctamente.
        $this->assertSame('ada@example.com', $preview['filas'][0]['datos']['mail']);
        $this->assertSame('adri@example.com', $preview['filas'][1]['datos']['mail']);
    }

    public function test_preview_marca_cambios_de_membresia_para_usuario_existente(): void
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        $entidad = Entidad::create(['nombre' => 'Entidad Cambios', 'abreviacion' => 'ECB', 'entidad_principal' => false]);
        // Abreviaciones únicas (la columna es UNIQUE global y el dump ya tiene COR/BEN/CLA).
        $actualMemb = Membresia::create(['nombre' => 'TK Test Actual', 'abreviacion' => 'ZZ1', 'entidad_id' => $entidad->id, 'valor' => 1]);
        Membresia::create(['nombre' => 'TK Test Nuevo', 'abreviacion' => 'ZZ2', 'entidad_id' => $entidad->id, 'valor' => 1]);

        // Usuario existente con membresía actual = ZZ1, online = false.
        $user = User::create(['name' => 'Existe', 'email' => 'existe@example.com', 'password' => Hash::make('x')]);
        $user->updateMembresiaUsuario([
            'membresia_id' => $actualMemb->id,
            'membresia_online' => false,
            'membresia_inscripcion_fecha' => now()->toDateString(),
        ]);

        // CSV: pasa a ZZ2 y online SI → ambos cambian.
        $contenido = implode("\n", [
            'Nombre,Apellido,Activo,TK,Programa,ONLINE,Mail,Teléfonos,Ciudad,Newsletter',
            'Existe,Apellido,A,ZZ2,PG,SI,existe@example.com,123,CABA,Si',
        ]);
        $csv = UploadedFile::fake()->createWithContent('cambios.csv', $contenido);

        $fila = app(ImportarMembresiasService::class)->previsualizar($csv, $entidad->id)['filas'][0];

        $this->assertTrue($fila['user_existe']);
        $this->assertSame('ZZ1', $fila['actual']['tk']);     // membresía actual
        $this->assertFalse($fila['actual']['online']);
        $this->assertTrue($fila['cambios']['membresia']);     // ZZ1 → ZZ2
        $this->assertTrue($fila['cambios']['online']);        // No → Sí
    }

    public function test_usuario_existente_sin_cambios_se_omite(): void
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        $entidad = Entidad::create(['nombre' => 'Entidad SinCambios', 'abreviacion' => 'ESC', 'entidad_principal' => false]);
        $memb = Membresia::create(['nombre' => 'TK SinCambios', 'abreviacion' => 'ZZ3', 'entidad_id' => $entidad->id, 'valor' => 1]);

        // Usuario cuyos datos coinciden EXACTAMENTE con la fila del CSV.
        $user = User::create([
            'name' => 'SinCambio Test',
            'email' => 'sc@example.com',
            'password' => Hash::make('x'),
            'telefono' => '111',
            'direccion' => 'CABA',
            'msgxmail' => true,
        ]);
        $user->updateMembresiaUsuario([
            'membresia_id' => $memb->id,
            'membresia_online' => true,
            'suscripcion' => false,
            'membresia_inscripcion_fecha' => now()->toDateString(),
        ]);

        // CSV idéntico al estado actual (sin Programa para no comparar ese campo).
        $contenido = implode("\n", [
            'Nombre,Apellido,Activo,TK,Programa,ONLINE,Mail,Teléfonos,Ciudad,Newsletter',
            'SinCambio,Test,A,ZZ3,,SI,sc@example.com,111,CABA,Si',
        ]);

        $service = app(ImportarMembresiasService::class);

        // Preview: la fila queda como "sin cambios", no cuenta como existente-actualiza ni membresía.
        $preview = $service->previsualizar(UploadedFile::fake()->createWithContent('sc.csv', $contenido), $entidad->id);
        $this->assertSame(1, $preview['sin_cambios']);
        $this->assertSame(0, $preview['usuarios_existentes']);
        $this->assertSame(0, $preview['membresias_a_asignar']);
        $this->assertTrue($preview['filas'][0]['sin_cambios']);

        // Import: se omite la actualización.
        $resumen = $service->importar(UploadedFile::fake()->createWithContent('sc.csv', $contenido), $entidad->id);
        $this->assertSame(1, $resumen['sin_cambios']);
        $this->assertSame(0, $resumen['actualizados']);
        $this->assertSame(0, $resumen['membresias_asignadas']);
        $this->assertSame(0, $resumen['creados']);
    }

    public function test_importa_pago_del_mes_al_estado_de_cuenta_y_es_idempotente(): void
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        $entidad = Entidad::create(['nombre' => 'Entidad Pago', 'abreviacion' => 'EPG', 'entidad_principal' => false]);
        $memb = Membresia::create(['nombre' => 'TK Pago', 'abreviacion' => 'ZZ9', 'entidad_id' => $entidad->id, 'valor' => 1000]);

        // Tres usuarios existentes con la membresía ZZ9 (no cambia su membresía al importar).
        foreach (['pago1' => 'p1@example.com', 'pago2' => 'p2@example.com', 'pago3' => 'p3@example.com'] as $nombre => $email) {
            $user = User::create(['name' => ucfirst($nombre) . ' Test', 'email' => $email, 'password' => Hash::make('x')]);
            $user->updateMembresiaUsuario([
                'membresia_id' => $memb->id,
                'membresia_online' => $nombre === 'pago1',
                'membresia_inscripcion_fecha' => now()->toDateString(),
            ]);
        }

        // CSV con las 4 columnas de pago: monto+Bco, Sponsor, y sin pago.
        $contenido = implode("\n", [
            'Nombre,Apellido,Activo,TK,Programa,ONLINE,Mail,Teléfonos,Ciudad,Newsletter,Imp.,Día,Modo,Nota',
            'Pago1,Test,A,ZZ9,,SI,p1@example.com,111,CABA,No,"$55,000",10/06,Bco,Pago de junio',
            'Pago2,Test,A,ZZ9,,NO,p2@example.com,222,CABA,No,Sponsor,,,Comentario',
            'Pago3,Test,A,ZZ9,,NO,p3@example.com,333,CABA,No,,,,Solo nota',
        ]);

        $service = app(ImportarMembresiasService::class);
        $mesActual = now()->format('Y-m');

        // Preview: 2 pagos a registrar (Pago1 y Pago2; Pago3 sin importe).
        $preview = $service->previsualizar(UploadedFile::fake()->createWithContent('pago.csv', $contenido), $entidad->id);
        $this->assertSame(2, $preview['pagos_a_registrar']);

        // Import.
        $resumen = $service->importar(UploadedFile::fake()->createWithContent('pago.csv', $contenido), $entidad->id);
        $this->assertSame(2, $resumen['pagos_registrados']);

        // Pago1: monto, modo mapeado a Transferencia, original en info_pago, observaciones de la nota.
        $p1 = EstadoCuentaMembresia::where('user_id', User::where('email', 'p1@example.com')->value('id'))->first();
        $this->assertNotNull($p1);
        $this->assertSame($mesActual, $p1->mes_pagado);
        $this->assertSame(55000.0, (float) $p1->importe);
        $this->assertSame('Transferencia', $p1->modo);
        $this->assertSame('Bco', $p1->info_pago);
        $this->assertSame('Pago de junio', $p1->observaciones);
        $this->assertTrue((bool) $p1->pagado);
        $this->assertNotNull($p1->fecha_pago);

        // Pago2: Sponsor => pagado con importe 0 y observación "Sponsor · ...".
        $p2 = EstadoCuentaMembresia::where('user_id', User::where('email', 'p2@example.com')->value('id'))->first();
        $this->assertNotNull($p2);
        $this->assertSame(0.0, (float) $p2->importe);
        $this->assertTrue((bool) $p2->pagado);
        $this->assertStringContainsString('Sponsor', $p2->observaciones);

        // Pago3: sin importe => no se crea estado de cuenta.
        $p3 = EstadoCuentaMembresia::where('user_id', User::where('email', 'p3@example.com')->value('id'))->count();
        $this->assertSame(0, $p3);

        // Idempotencia: reimportar no duplica los registros del mes.
        $service->importar(UploadedFile::fake()->createWithContent('pago.csv', $contenido), $entidad->id);
        $total = EstadoCuentaMembresia::whereIn('user_id', User::whereIn('email', ['p1@example.com', 'p2@example.com', 'p3@example.com'])->pluck('id'))
            ->where('mes_pagado', $mesActual)
            ->count();
        $this->assertSame(2, $total);
    }
}
