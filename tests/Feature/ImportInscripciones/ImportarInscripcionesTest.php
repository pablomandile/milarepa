<?php

namespace Tests\Feature\ImportInscripciones;

use App\Models\Actividad;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use App\Services\ImportarInscripcionesService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Importación de inscripciones legacy desde CSV a una Actividad.
 * Cubre: alta de usuario+inscripción, match de usuario existente, mapeo de
 * membresía, parseo de pago, y dedupe por email (en el CSV y contra inscripciones
 * existentes) → idempotencia.
 */
class ImportarInscripcionesTest extends TestCase
{
    use DatabaseTransactions;

    private function asegurarPaisDefault(): void
    {
        DB::statement(
            'INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())',
            ['Argentina'],
        );
    }

    /** Crea entidad + membresías + actividad de prueba y devuelve [actividad, membresiaCorazon]. */
    private function escenario(): array
    {
        $this->asegurarPaisDefault();

        $entidad = Entidad::create(['nombre' => 'Entidad Test Import', 'abreviacion' => 'ETI', 'entidad_principal' => false]);
        $corazon = Membresia::create(['nombre' => 'TK Corazón', 'abreviacion' => 'CORTI', 'entidad_id' => $entidad->id, 'valor' => 55000]);
        Membresia::create(['nombre' => 'Tk Benefactor', 'abreviacion' => 'BENTI', 'entidad_id' => $entidad->id, 'valor' => 70000]);

        $actividad = Actividad::create([
            'nombre' => 'Evento Test Import',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Test'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial Test'])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema Test'])->id,
            'entidad_id' => $entidad->id,
            'fecha_inicio' => '2026-06-13 10:00:00',
            'fecha_fin' => '2026-06-14 20:00:00',
            'estado' => true,
        ]);

        return [$actividad, $corazon];
    }

    private function csv(): UploadedFile
    {
        $contenido = implode("\n", [
            'Marca temporal,Nombre,Apellido,Dirección de correo electrónico,Teléfono celular,Membresía,Pendiente,Valor,FechaPago,Forma',
            '13/06/2026 10:00:00,Ana,Nueva,ana.nueva@example.com,111,TK CORAZÓN,$40000,40000,2/5,MP1234',
            '13/06/2026 10:01:00,Beto,Sinmem,beto.sinmem@example.com,222,SIN MEMBRESIA,$60000,,,',
            '13/06/2026 10:02:00,Carla,Existe,carla.existe@example.com,333,TK BENEFACTOR,$30000,,,',
            '13/06/2026 10:03:00,Ana,Dup,ana.nueva@example.com,444,TK CORAZÓN,$40000,,,',
            '13/06/2026 10:04:00,Yain,Inscripto,ya.inscripto@example.com,666,TK CORAZÓN,$40000,,,',
        ]);

        return UploadedFile::fake()->createWithContent('inscripciones.csv', $contenido);
    }

    /** Usuario existente + usuario ya inscripto a la actividad. */
    private function sembrarUsuarios(Actividad $actividad): void
    {
        User::create(['name' => 'Carla', 'email' => 'carla.existe@example.com', 'password' => Hash::make('x')]);

        $yaInscripto = User::create(['name' => 'Yain', 'email' => 'ya.inscripto@example.com', 'password' => Hash::make('x')]);
        Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $yaInscripto->id,
            'membresia' => 'TK Corazón',
            'precioGeneral' => 0,
            'montoapagar' => 0,
            'pago' => 'Saldado',
            'estado' => 'Confirmada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);
    }

    public function test_preview_clasifica_filas_y_no_toca_la_bd(): void
    {
        [$actividad] = $this->escenario();
        $this->sembrarUsuarios($actividad);
        $usuariosAntes = User::count();

        $preview = app(ImportarInscripcionesService::class)->previsualizar($this->csv(), $actividad->id);

        $this->assertSame(5, $preview['total_filas']);
        $this->assertSame(3, $preview['a_crear']);          // ana, beto, carla
        $this->assertSame(2, $preview['usuarios_nuevos']);  // ana, beto
        $this->assertSame(1, $preview['usuarios_existentes']); // carla
        $this->assertSame(2, $preview['omitidas']);         // dup ana + ya inscripto
        $this->assertSame(0, $preview['errores']);

        // Preview no persiste nada.
        $this->assertSame($usuariosAntes, User::count());
    }

    public function test_importar_crea_usuarios_e_inscripciones_con_pago_y_membresia(): void
    {
        [$actividad, $corazon] = $this->escenario();
        $this->sembrarUsuarios($actividad);

        $resumen = app(ImportarInscripcionesService::class)->importar($this->csv(), $actividad->id);

        $this->assertSame(3, $resumen['creadas']);
        $this->assertSame(2, $resumen['omitidas']);
        // 1 pre-existente (ya.inscripto) + 3 nuevas = 4
        $this->assertSame(4, Inscripcion::where('actividad_id', $actividad->id)->count());

        // Usuario nuevo creado con rol y membresía.
        $ana = User::where('email', 'ana.nueva@example.com')->first();
        $this->assertNotNull($ana);
        $this->assertTrue($ana->hasRole('asistant'));
        $this->assertSame($corazon->id, $ana->membresiaUsuario?->membresia_id);

        // Inscripción de Ana: pago saldado con fecha y referencia.
        $insAna = Inscripcion::where('user_id', $ana->id)->where('actividad_id', $actividad->id)->first();
        $this->assertSame('Saldado', $insAna->pago);
        $this->assertSame('Confirmada', $insAna->estado);
        $this->assertEquals(40000, (float) $insAna->montoapagar);
        $this->assertSame('TK Corazón', $insAna->membresia);
        $this->assertSame('2026-05-02', $insAna->fecha_pago->toDateString());
        $this->assertSame('MP1234', $insAna->referencia_pago);
        $this->assertSame('2026-06-13 10:00:00', $insAna->created_at->toDateTimeString());
        // Importadas: la confirmación queda pendiente (el envío se probará luego).
        $this->assertSame('Pendiente', $insAna->envioConfirmacion);

        // Beto: sin membresía, impago.
        $beto = User::where('email', 'beto.sinmem@example.com')->first();
        $insBeto = Inscripcion::where('user_id', $beto->id)->first();
        $this->assertSame('Pendiente', $insBeto->pago);
        $this->assertEquals(60000, (float) $insBeto->montoapagar);
        $this->assertSame('Sin membresía', $insBeto->membresia);

        // Carla: usuario existente reutilizado (no se duplica).
        $this->assertSame(1, User::where('email', 'carla.existe@example.com')->count());
        $this->assertSame(1, Inscripcion::whereHas('user', fn ($q) => $q->where('email', 'carla.existe@example.com'))
            ->where('actividad_id', $actividad->id)->count());
    }

    public function test_reporta_columnas_desconocidas_y_no_las_conocidas_ignoradas(): void
    {
        [$actividad] = $this->escenario();

        // Header con una columna desconocida ("Notas internas") y una conocida-ignorada
        // ("Confirmá tu dirección de correo electrónico", que NO debe reportarse).
        $contenido = implode("\n", [
            'Marca temporal,Nombre,Apellido,Dirección de correo electrónico,Confirmá tu dirección de correo electrónico,Membresía,Pendiente,Notas internas',
            '13/06/2026 10:00:00,Ana,Nueva,ana.nueva@example.com,ana.nueva@example.com,TK CORAZÓN,$40000,algo',
        ]);
        $csv = UploadedFile::fake()->createWithContent('inscripciones.csv', $contenido);

        $preview = app(ImportarInscripcionesService::class)->previsualizar($csv, $actividad->id);

        $this->assertContains('Notas internas', $preview['columnas_desconocidas']);
        $this->assertNotContains('Confirmá tu dirección de correo electrónico', $preview['columnas_desconocidas']);
        $this->assertNotContains('Membresía', $preview['columnas_desconocidas']);
    }

    public function test_importar_guarda_reporte_json(): void
    {
        Storage::fake('local');
        [$actividad] = $this->escenario();
        $user = User::create(['name' => 'Operador', 'email' => 'op@example.com', 'password' => Hash::make('x')]);

        $this->actingAs($user)
            ->post(route('estadoinscripciones.importar.confirmar'), [
                'actividad_id' => $actividad->id,
                'archivo' => $this->csv(),
            ]);

        $archivos = Storage::disk('local')->files('import-reports/inscripciones');
        $this->assertCount(1, $archivos);

        $reporte = json_decode((string) Storage::disk('local')->get($archivos[0]), true);
        $this->assertSame($actividad->id, $reporte['actividad_id']);
        // Sin sembrar usuarios previos: ana, beto, carla, ya.inscripto se crean; la fila dup de ana se omite.
        $this->assertSame(4, $reporte['resumen']['creadas']);
        $this->assertSame('Operador', $reporte['usuario']);
    }

    public function test_modalidad_online_marca_online_y_reporta_falta_de_membresia_en_observaciones(): void
    {
        [$actividad, $corazon] = $this->escenario();

        // Usuario con membresía ONLINE.
        $conOnline = User::create(['name' => 'Con Online', 'email' => 'con.online@example.com', 'password' => Hash::make('x')]);
        $conOnline->updateMembresiaUsuario([
            'membresia_id' => $corazon->id,
            'membresia_online' => true,
            'membresia_inscripcion_fecha' => now()->toDateString(),
        ]);

        // Usuario existente SIN membresía online.
        User::create(['name' => 'Sin Online', 'email' => 'sin.online@example.com', 'password' => Hash::make('x')]);

        $contenido = implode("\n", [
            'Marca temporal,¿Bajo que modalidad vas a participar?,Dirección de correo electrónico TK (ONLINE),Nombre,Apellido,Dirección de correo electrónico,Membresía,Pendiente',
            '13/06/2026 10:00:00,ONLINE,con.online@example.com,Con,Online,con.online@example.com,TK CORAZÓN,$0',
            '13/06/2026 10:01:00,ONLINE,,Sin,Online,sin.online@example.com,SIN MEMBRESIA,$0',
            '13/06/2026 10:02:00,PRESENCIAL,,Pres,Encial,pres.encial@example.com,SIN MEMBRESIA,$30000',
        ]);
        $csv = UploadedFile::fake()->createWithContent('online.csv', $contenido);

        $resumen = app(ImportarInscripcionesService::class)->importar($csv, $actividad->id);

        // Ahora las 3 se crean (ya no se omite la online sin membresía).
        $this->assertSame(3, $resumen['creadas']);
        $this->assertSame(0, $resumen['omitidas']);

        // Online con membresía: online = true, link de stream pendiente, sin aviso de falta.
        $insOnline = Inscripcion::whereHas('user', fn ($q) => $q->where('email', 'con.online@example.com'))
            ->where('actividad_id', $actividad->id)->first();
        $this->assertNotNull($insOnline);
        $this->assertTrue((bool) $insOnline->online);
        $this->assertSame('Pendiente', $insOnline->envioLinkStream);
        $this->assertStringNotContainsString('sin membresía online', (string) $insOnline->observaciones);

        // Online SIN membresía: se inscribe igual (online = true) y se reporta en observaciones.
        $insSinOnline = Inscripcion::whereHas('user', fn ($q) => $q->where('email', 'sin.online@example.com'))
            ->where('actividad_id', $actividad->id)->first();
        $this->assertNotNull($insSinOnline);
        $this->assertTrue((bool) $insSinOnline->online);
        $this->assertSame('Pendiente', $insSinOnline->envioLinkStream);
        $this->assertStringContainsString('sin membresía online', (string) $insSinOnline->observaciones);

        // Presencial: online = false, sin envío de stream.
        $insPres = Inscripcion::whereHas('user', fn ($q) => $q->where('email', 'pres.encial@example.com'))
            ->where('actividad_id', $actividad->id)->first();
        $this->assertNotNull($insPres);
        $this->assertFalse((bool) $insPres->online);
        $this->assertSame('No aplica', $insPres->envioLinkStream);
    }

    public function test_inscripcion_sin_monto_queda_saldada_y_con_monto_pendiente(): void
    {
        [$actividad] = $this->escenario();

        $contenido = implode("\n", [
            'Marca temporal,Nombre,Apellido,Dirección de correo electrónico,Membresía,Pendiente',
            '13/06/2026 10:00:00,Gratis,Uno,gratis.uno@example.com,TK CORAZÓN,$0',
            '13/06/2026 10:01:00,Paga,Dos,paga.dos@example.com,SIN MEMBRESIA,$30000',
        ]);
        $csv = UploadedFile::fake()->createWithContent('gratis.csv', $contenido);

        app(ImportarInscripcionesService::class)->importar($csv, $actividad->id);

        // Sin monto a pagar (gratuita) => Saldado / Confirmada.
        $gratis = Inscripcion::whereHas('user', fn ($q) => $q->where('email', 'gratis.uno@example.com'))
            ->where('actividad_id', $actividad->id)->first();
        $this->assertNotNull($gratis);
        $this->assertSame('Saldado', $gratis->pago);
        $this->assertSame('Confirmada', $gratis->estado);
        $this->assertEquals(0, (float) $gratis->montoapagar);

        // Con monto impago => Pendiente / Registrada.
        $paga = Inscripcion::whereHas('user', fn ($q) => $q->where('email', 'paga.dos@example.com'))
            ->where('actividad_id', $actividad->id)->first();
        $this->assertNotNull($paga);
        $this->assertSame('Pendiente', $paga->pago);
        $this->assertSame('Registrada', $paga->estado);
    }

    public function test_membresia_sin_entidad_se_mapea_a_la_entidad_principal(): void
    {
        $this->asegurarPaisDefault();

        // La entidad principal (del entorno) debe tener una membresía "TK Corazón".
        $principal = Entidad::where('entidad_principal', true)->orderBy('id')->first();
        $this->assertNotNull($principal, 'El entorno de test debe tener una entidad principal.');
        $corazonPrincipal = Membresia::where('entidad_id', $principal->id)->get()
            ->first(fn ($m) => str_contains(mb_strtolower($m->nombre), 'coraz'));
        $this->assertNotNull($corazonPrincipal, 'La entidad principal debe tener "TK Corazón".');

        // Actividad en una entidad anexo que NO tiene la membresía "TK Corazón".
        $anexo = Entidad::create(['nombre' => 'Anexo Fallback Test', 'abreviacion' => 'AFT', 'entidad_principal' => false]);
        $actividad = Actividad::create([
            'nombre' => 'Evento Anexo Fallback',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Fallback'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Modalidad Fallback'])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema Fallback'])->id,
            'entidad_id' => $anexo->id,
            'fecha_inicio' => '2026-06-13 10:00:00',
            'fecha_fin' => '2026-06-14 20:00:00',
            'estado' => true,
        ]);

        $contenido = implode("\n", [
            'Marca temporal,Nombre,Apellido,Dirección de correo electrónico,Membresía,Pendiente',
            '13/06/2026 10:00:00,Fall,Back,fall.back@example.com,TK CORAZÓN,$0',
        ]);
        $csv = UploadedFile::fake()->createWithContent('fallback.csv', $contenido);

        $resumen = app(ImportarInscripcionesService::class)->importar($csv, $actividad->id);
        $this->assertSame(1, $resumen['creadas']);

        // Inscripción y usuario quedan asociados a la membresía de la entidad principal.
        $ins = Inscripcion::whereHas('user', fn ($q) => $q->where('email', 'fall.back@example.com'))
            ->where('actividad_id', $actividad->id)->first();
        $this->assertNotNull($ins);
        $this->assertSame($corazonPrincipal->nombre, $ins->membresia);

        $user = User::where('email', 'fall.back@example.com')->first();
        $this->assertSame($corazonPrincipal->id, $user->membresiaUsuario?->membresia_id);
    }

    public function test_reimportar_el_mismo_csv_no_crea_duplicados(): void
    {
        [$actividad] = $this->escenario();
        $this->sembrarUsuarios($actividad);
        $service = app(ImportarInscripcionesService::class);

        $service->importar($this->csv(), $actividad->id);
        $totalTrasPrimera = Inscripcion::where('actividad_id', $actividad->id)->count();

        $segunda = $service->importar($this->csv(), $actividad->id);

        $this->assertSame(0, $segunda['creadas']);
        $this->assertSame(5, $segunda['omitidas']); // las 5 filas ya inscriptas/dup
        $this->assertSame($totalTrasPrimera, Inscripcion::where('actividad_id', $actividad->id)->count());
    }
}
