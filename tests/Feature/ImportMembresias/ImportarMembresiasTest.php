<?php

namespace Tests\Feature\ImportMembresias;

use App\Models\Entidad;
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
}
