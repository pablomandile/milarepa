<?php

namespace Tests\Feature\ImportMultievento;

use App\Models\Actividad;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use App\Services\ImportarMultieventoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Importación "multievento" desde la planilla maestra consolidada.
 * Cubre: ruteo por evento (con sugerencia por nombre+fecha), corte por fecha,
 * identidad con email compartido (placeholder), dedupe/idempotencia, Pago=NO,
 * eventos sin actividad, y descarga desde la URL de la planilla.
 */
class ImportarMultieventoTest extends TestCase
{
    use DatabaseTransactions;

    private const HEADERS = [
        'Id_Inscripcion', 'Nombre', 'Apellido', 'email', 'Celular', 'Telefono_WA',
        'BarrioNormalizado', 'MedioComunicacion', 'Membresia', 'RecibirInfoTk', 'Pendiente',
        'Valor', 'FechaPago', 'Forma', 'Pago', 'Asistencia', 'Modalidad', 'Marca_temporal',
        'NombreEvento', 'FechaEvento', 'Comentarios', 'Confirmado_Manual', '__Hash',
    ];

    private function asegurarPaisDefault(): void
    {
        DB::statement(
            'INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())',
            ['Argentina'],
        );
    }

    /** @return array{curso: Actividad, caminata: Actividad} */
    private function escenario(): array
    {
        $this->asegurarPaisDefault();

        $entidad = Entidad::create(['nombre' => 'Entidad ME', 'abreviacion' => 'EME', 'entidad_principal' => false]);
        $tipo = TipoActividad::create(['nombre' => 'Tipo ME']);
        $mod = Modalidad::create(['nombre' => 'Presencial ME']);
        $esq = EsquemaPrecio::create(['nombre' => 'Esquema ME']);

        $mk = fn (string $nombre, string $fecha) => Actividad::create([
            'nombre' => $nombre,
            'tipo_actividad_id' => $tipo->id,
            'modalidad_id' => $mod->id,
            'esquema_precio_id' => $esq->id,
            'entidad_id' => $entidad->id,
            'fecha_inicio' => $fecha . ' 10:00:00',
            'fecha_fin' => $fecha . ' 20:00:00',
            'estado' => true,
        ]);

        return [
            'curso' => $mk('Curso mensual', '2026-06-06'),
            'caminata' => $mk('Caminata y meditación', '2026-06-28'),
        ];
    }

    private function armarCsv(array $filas): string
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, self::HEADERS);
        foreach ($filas as $fila) {
            fputcsv($fp, array_map(fn ($h) => $fila[$h] ?? '', self::HEADERS));
        }
        rewind($fp);

        return stream_get_contents($fp);
    }

    /** CSV de prueba con 3 eventos post-corte + 1 fila pre-corte + email compartido. */
    private function csvPrueba(): string
    {
        $curso = 'ROSARIO "Curso mensual" con Kelsang Panchen';
        $caminata = 'RESERVA ECOLÓGICA "Caminata y meditación" con guen Kelsang Rinchung';

        return $this->armarCsv([
            ['Id_Inscripcion' => 'i1', 'Nombre' => 'Flavio', 'Apellido' => 'Priotti', 'email' => 'flavio@example.com', 'Celular' => '3410000001', 'Telefono_WA' => '5493410000001', 'Membresia' => 'SIN MEMBRESIA', 'Pendiente' => '10000', 'Pago' => 'CH', 'Marca_temporal' => '19/05/2026 14:56:42', 'NombreEvento' => $curso, 'FechaEvento' => '6/6/2026', '__Hash' => 'h1'],
            ['Id_Inscripcion' => 'i2', 'Nombre' => 'Silvia', 'Apellido' => 'Tapia', 'email' => 'silvia@example.com', 'Celular' => '3410000002', 'Membresia' => 'SIN MEMBRESIA', 'Pendiente' => '0', 'Valor' => '10000', 'FechaPago' => '5/6/2026', 'Forma' => 'Bco', 'Pago' => 'CH', 'Asistencia' => '1', 'Marca_temporal' => '22/05/2026 11:56:39', 'NombreEvento' => $curso, 'FechaEvento' => '6/6/2026', 'Comentarios' => 'Pagó en efectivo', '__Hash' => 'h2'],
            // Email compartido: dos personas distintas bajo irene@example.com, mismo evento.
            ['Id_Inscripcion' => 'i3', 'Nombre' => 'Claudio', 'Apellido' => 'Beron', 'email' => 'irene@example.com', 'Celular' => '3410000003', 'Membresia' => 'SIN MEMBRESIA', 'Pendiente' => '10000', 'Pago' => 'CH', 'Marca_temporal' => '27/05/2026 10:00:07', 'NombreEvento' => $curso, 'FechaEvento' => '6/6/2026', '__Hash' => 'h3'],
            ['Id_Inscripcion' => 'i4', 'Nombre' => 'Maria', 'Apellido' => 'Rista', 'email' => 'irene@example.com', 'Celular' => '3410000004', 'Membresia' => 'SIN MEMBRESIA', 'Pendiente' => '10000', 'Pago' => 'CH', 'Marca_temporal' => '27/05/2026 10:01:07', 'NombreEvento' => $curso, 'FechaEvento' => '6/6/2026', '__Hash' => 'h4'],
            // Evento Caminata, Pago=NO (sin costo).
            ['Id_Inscripcion' => 'i5', 'Nombre' => 'Agustina', 'Apellido' => 'Sanchez', 'email' => 'agustina@example.com', 'Celular' => '1130000001', 'Telefono_WA' => '5491130000001', 'Membresia' => 'SIN MEMBRESIA', 'Pago' => 'NO', 'Marca_temporal' => '22/06/2026 09:03:44', 'NombreEvento' => $caminata, 'FechaEvento' => '28/6/2026', '__Hash' => 'h5'],
            // Fila pre-corte (diciembre 2025) → descartada por fecha (corte por defecto: 2026-01-01).
            ['Id_Inscripcion' => 'i6', 'Nombre' => 'Karina', 'Apellido' => 'Puente', 'email' => 'karina@example.com', 'Celular' => '1160000001', 'Membresia' => 'SIN MEMBRESIA', 'Pendiente' => '15000', 'Pago' => 'CH', 'Marca_temporal' => '15/12/2025 19:16:24', 'NombreEvento' => 'PALERMO "Técnicas budistas" con guen', 'FechaEvento' => '18/12/2025', '__Hash' => 'h6'],
            // Evento post-corte sin comillas y sin actividad que matchee → sin_actividad si no se mapea.
            ['Id_Inscripcion' => 'i7', 'Nombre' => 'Edna', 'Apellido' => 'Mostyszczer', 'email' => 'edna@example.com', 'Celular' => '1130000002', 'Membresia' => 'SIN MEMBRESIA', 'Pendiente' => '20000', 'Pago' => 'CH', 'Modalidad' => 'PRESENCIAL', 'Marca_temporal' => '30/06/2026 09:32:42', 'NombreEvento' => 'PALERMO Taller: Karma con Sebastian', 'FechaEvento' => '12/7/2026', '__Hash' => 'h7'],
        ]);
    }

    /** Mapeo evento→actividad a partir de las sugerencias del preview. */
    private function mapeoDesdeSugerencias(array $preview): array
    {
        $mapeo = [];
        foreach ($preview['eventos'] as $ev) {
            if ($ev['sugerida_id']) {
                $mapeo[$ev['clave']] = $ev['sugerida_id'];
            }
        }

        return $mapeo;
    }

    public function test_preview_agrupa_eventos_sugiere_actividad_y_aplica_corte(): void
    {
        ['curso' => $curso, 'caminata' => $caminata] = $this->escenario();
        $service = app(ImportarMultieventoService::class);

        $preview = $service->previsualizar($this->csvPrueba());

        $this->assertSame(7, $preview['total_filas']);
        $this->assertSame(1, $preview['descartadas_fecha'], 'La fila de diciembre 2025 debe descartarse por fecha.');
        $this->assertSame(1, $preview['sin_actividad'], 'El evento Karma (sin match) queda sin actividad.');
        $this->assertSame(5, $preview['a_crear']);

        // 3 eventos post-corte (Curso, Caminata, Karma). Enero no cuenta como evento.
        $this->assertCount(3, $preview['eventos']);
        $porNombre = collect($preview['eventos'])->keyBy('nombre_evento');
        $this->assertSame($curso->id, $porNombre['ROSARIO "Curso mensual" con Kelsang Panchen']['sugerida_id']);
        $this->assertSame($caminata->id, $porNombre['RESERVA ECOLÓGICA "Caminata y meditación" con guen Kelsang Rinchung']['sugerida_id']);
        $this->assertNull($porNombre['PALERMO Taller: Karma con Sebastian']['sugerida_id']);
    }

    public function test_importar_crea_inscripciones_por_evento_y_placeholder_para_email_compartido(): void
    {
        ['curso' => $curso, 'caminata' => $caminata] = $this->escenario();
        $service = app(ImportarMultieventoService::class);
        $csv = $this->csvPrueba();

        $mapeo = $this->mapeoDesdeSugerencias($service->previsualizar($csv));
        $resumen = $service->importar($csv, $mapeo);

        $this->assertSame(5, $resumen['creadas']);
        $this->assertSame(1, $resumen['descartadas_fecha']);
        $this->assertSame(1, $resumen['sin_actividad']);

        $this->assertSame(4, Inscripcion::where('actividad_id', $curso->id)->count());
        $this->assertSame(1, Inscripcion::where('actividad_id', $caminata->id)->count());

        // Email compartido: el segundo (Maria Rista) se crea con email placeholder, no se colapsa.
        $this->assertDatabaseHas('users', ['email' => 'irene@example.com', 'name' => 'Claudio Beron']);
        $this->assertDatabaseHas('users', ['email' => 'irene.i.maria.rista@import.local', 'name' => 'Maria Rista']);

        // whatsapp desde Telefono_WA.
        $this->assertDatabaseHas('users', ['email' => 'flavio@example.com', 'whatsapp' => '5493410000001']);

        // i2 pagó (Valor+FechaPago+Forma) → Saldado/Confirmada; asistencia Presente.
        $silvia = User::where('email', 'silvia@example.com')->first();
        $insSilvia = Inscripcion::where('user_id', $silvia->id)->where('actividad_id', $curso->id)->first();
        $this->assertSame('Saldado', $insSilvia->pago);
        $this->assertSame('Confirmada', $insSilvia->estado);
        $this->assertSame('Presente', $insSilvia->asistencia);

        // i5 Pago=NO → sin costo (monto 0) y Saldado.
        $agustina = User::where('email', 'agustina@example.com')->first();
        $insAgustina = Inscripcion::where('user_id', $agustina->id)->first();
        $this->assertSame('0.00', (string) $insAgustina->montoapagar);
        $this->assertSame('Saldado', $insAgustina->pago);
    }

    public function test_reimportar_el_mismo_archivo_no_duplica(): void
    {
        $this->escenario();
        $service = app(ImportarMultieventoService::class);
        $csv = $this->csvPrueba();
        $mapeo = $this->mapeoDesdeSugerencias($service->previsualizar($csv));

        $primera = $service->importar($csv, $mapeo);
        $this->assertSame(5, $primera['creadas']);

        $segunda = $service->importar($csv, $mapeo);
        $this->assertSame(0, $segunda['creadas'], 'Re-importar no debe crear duplicados.');
        $this->assertSame(5, $segunda['omitidas']);

        $this->assertSame(5, Inscripcion::count());
    }

    public function test_corte_por_fecha_es_configurable(): void
    {
        $this->escenario();
        config(['multievento.fecha_corte' => '2026-07-01']);
        $service = app(ImportarMultieventoService::class);

        $preview = $service->previsualizar($this->csvPrueba());

        // Con corte en julio, Curso (junio), Caminata (junio) y dic 2025 quedan fuera; solo Karma (julio) sobrevive.
        $this->assertSame(6, $preview['descartadas_fecha']);
        $this->assertSame(1, $preview['sin_actividad']);
        $this->assertSame(0, $preview['a_crear']);
    }

    public function test_guarda_el_mapeo_confirmado_y_lo_sugiere_en_la_proxima_importacion(): void
    {
        ['curso' => $curso] = $this->escenario();

        // Actividad cuyo nombre NO coincide con el NombreEvento del taller de Karma → sin sugerencia automática.
        $karmaAct = Actividad::create([
            'nombre' => 'Encuentro de julio',
            'tipo_actividad_id' => $curso->tipo_actividad_id,
            'modalidad_id' => $curso->modalidad_id,
            'esquema_precio_id' => $curso->esquema_precio_id,
            'entidad_id' => $curso->entidad_id,
            'fecha_inicio' => '2026-07-12 10:00:00',
            'fecha_fin' => '2026-07-12 20:00:00',
            'estado' => true,
        ]);

        $service = app(ImportarMultieventoService::class);
        $csv = $this->csvPrueba();

        // 1ª importación: el evento Karma no tiene sugerencia; se mapea a mano.
        $preview1 = $service->previsualizar($csv);
        $karma1 = collect($preview1['eventos'])->firstWhere('nombre_evento', 'PALERMO Taller: Karma con Sebastian');
        $this->assertNotNull($karma1);
        $this->assertNull($karma1['sugerida_id'], 'Karma no debería tener sugerencia automática.');

        $mapeo = $this->mapeoDesdeSugerencias($preview1);
        $mapeo[$karma1['clave']] = $karmaAct->id;
        $service->importar($csv, $mapeo);

        // El match confirmado quedó guardado.
        $this->assertDatabaseHas('multievento_mapeos', [
            'clave' => $karma1['clave'],
            'actividad_id' => $karmaAct->id,
        ]);

        // 2ª importación (sin pasar mapeo): el evento Karma ya se sugiere desde el match guardado.
        $preview2 = $service->previsualizar($csv);
        $karma2 = collect($preview2['eventos'])->firstWhere('nombre_evento', 'PALERMO Taller: Karma con Sebastian');
        $this->assertSame($karmaAct->id, $karma2['sugerida_id']);
        $this->assertSame('guardado', $karma2['origen_sugerencia']);
        $this->assertSame(0, $preview2['sin_actividad'], 'Con el match guardado, ya no hay eventos sin actividad.');
    }

    public function test_descarga_planilla_desde_url(): void
    {
        $csv = $this->csvPrueba();
        Http::fake([
            'docs.google.com/*' => Http::response($csv, 200, ['Content-Type' => 'text/csv; charset=utf-8']),
        ]);

        $service = app(ImportarMultieventoService::class);
        $bajado = $service->descargarPlanilla();

        $this->assertStringContainsString('NombreEvento', $bajado);
        $this->assertStringContainsString('Curso mensual', $bajado);
    }

    public function test_descarga_planilla_privada_lanza_error(): void
    {
        Http::fake([
            'docs.google.com/*' => Http::response('<html><body>Sign in</body></html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $service = app(ImportarMultieventoService::class);

        $this->expectException(\RuntimeException::class);
        $service->descargarPlanilla();
    }
}
