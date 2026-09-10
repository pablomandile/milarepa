<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deja una COPIA de la base de un centro lista para que la use otro centro.
 *
 * La base de una instalación nueva se clona de una existente porque los catálogos
 * (países, provincias, municipios, barrios, localidades, tipos de actividad,
 * modalidades, estados…) no tienen seeder: sólo existen como datos. Pero esa copia
 * viene con TODO lo del centro original, incluidos datos personales de miles de
 * personas. Este comando borra eso y deja los catálogos y las plantillas.
 *
 * Pensado para correrse sobre una copia local, antes de subir nada. Las guardas no
 * son decorativas: en este proyecto ya hubo un `migrate:fresh` que se llevó puesta
 * la base real (ver SECURITY_AUDIT.md).
 */
class PrepararCentroNuevo extends Command
{
    protected $signature = 'centro:preparar
        {--base= : Nombre EXACTO de la base sobre la que se va a operar. Obligatorio.}
        {--entidad= : Nombre del centro nuevo, para crear su entidad principal.}
        {--ejecutar : Aplica los cambios. Sin esto sólo muestra lo que haría.}';

    protected $description = 'Despersonaliza una copia de la base para instalarla en otro centro.';

    /** Bases que este comando se niega a tocar, pase lo que pase. */
    private const BASES_PROHIBIDAS = ['u302133020_milarepa', 'milarepa'];

    /** Datos del centro original: personas, movimiento y operación. Se vacían. */
    private const VACIAR = [
        // Personas y sesión.
        'users', 'model_has_roles', 'model_has_permissions', 'guest_users',
        'invitados', 'invitado_comida', 'invitado_hospedaje', 'invitado_transporte',
        'sessions', 'password_reset_tokens', 'personal_access_tokens',
        // Movimiento.
        'inscripciones', 'inscripciones_clases', 'inscripcion_clase_items', 'inscripcion_comida',
        'excencion_pago', 'cobros', 'cobro_comprobantes', 'estado_cuenta_membresias',
        'membresia_usuario', 'asistencias', 'ventas', 'venta_pos', 'venta_pos_items',
        'prestamos_anexos', 'devoluciones_anexos', 'historico_pedidos_libros',
        // Stock real del centro original. El catálogo de productos sí se conserva.
        'inventario_entidad_libro', 'inventario_entidad_oracion', 'inventario_entidad_arte',
        'inventario_entidad_otro', 'inventario_entidad_articulo_tienda',
        // Operación del centro original.
        'envios_mails', 'tickets', 'multievento_mapeos', 'grabaciones', 'links_grabacion',
        'programa_grabacion', 'streams', 'links', 'novedades', 'imagenes', 'versiones',
        'failed_jobs',
        // Sedes y alojamientos del centro original, con mails y teléfonos de terceros
        // (casas de retiro). Para otro centro no sirven y son datos de otras personas.
        'lugares', 'lugares_hospedaje', 'hospedajes', 'actividad_hospedaje',
        // Maestros y coordinadores son nombres de personas reales.
        'maestros', 'coordinadores', 'actividad_maestro', 'actividad_coordinador',
        'clase_maestro', 'clase_coordinador',
        // Preferencias del centro original: todas tienen default en el código.
        'configuraciones_sistema',
    ];

    /** Catálogos, permisos y plantillas: la razón de clonar en vez de migrar de cero. */
    private const CONSERVAR = [
        // Geografía y catálogos de sistema.
        'paises', 'provincias', 'municipios', 'barrios', 'localidades', 'sexos', 'monedas',
        'estados_actividad', 'estados_ticket', 'tipos_actividad', 'modalidades',
        'disponibilidades', 'comidas', 'transportes', 'programas', 'programa_estudios',
        'categorias_tienda',
        // Permisos: sin esto la app no funciona.
        'roles', 'permissions', 'role_has_permissions',
        // Historial de migraciones: sin esto el próximo deploy intenta migrar todo de nuevo.
        'migrations',
        // Plantillas que el centro nuevo edita en vez de crear de cero.
        'actividades', 'actividad_comida', 'actividad_transporte',
        'clases', 'ciclos', 'membresias', 'esquema_precios', 'esquema_descuentos',
        'esquema_precio_membresias', 'esquema_descuento_membresias', 'precio_grupos',
        'precio_grupo_membresias', 'servicio_precios',
        'descripciones', 'paginas_actividades_online', 'oraciones_cantadas',
        'frases_de_dharma', 'tutoriales', 'acerca_de',
        // Catálogo de productos, no el stock (que se vacía arriba).
        'libros', 'oraciones', 'arte', 'otros', 'articulos_tienda',
        'email_plantillas', 'email_envio_configuraciones',
    ];

    /** Quedan, pero con datos del centro original adentro. Ver despersonalizar(). */
    private const LIMPIAR = [
        'entidades',                   // se borran todas y se crea la del centro nuevo
        'metodos_pago',                // descripcion trae CBU/alias reales
        'botones_pago',                // link trae links de pago reales
        'actividad_metodo_pago',       // se conserva, pero repuntada a la entidad nueva
    ];

    public function handle(): int
    {
        $base = (string) DB::getDatabaseName();
        $declarada = (string) $this->option('base');

        $this->line('');
        $this->info("Base conectada: {$base}");

        if (in_array($base, self::BASES_PROHIBIDAS, true)) {
            $this->error('Esa es una base de producción de Milarepa. Este comando no opera sobre ella.');

            return self::FAILURE;
        }

        if ($declarada === '' || $declarada !== $base) {
            $this->error('Pasá --base con el nombre EXACTO de la base conectada, para confirmar sobre cuál operás.');
            $this->line("   Esperado: --base={$base}");

            return self::FAILURE;
        }

        // Nadie se acuerda de 106 tablas: si aparece una sin clasificar el comando
        // para, en vez de dejarla pasar con datos del centro original adentro.
        $enLaBase = collect(DB::select('SHOW TABLES'))
            ->map(fn ($fila) => array_values((array) $fila)[0])
            ->sort()
            ->values();

        $clasificadas = collect(self::VACIAR)->merge(self::CONSERVAR)->merge(self::LIMPIAR);
        $sinClasificar = $enLaBase->diff($clasificadas);
        $inexistentes = $clasificadas->diff($enLaBase);

        if ($sinClasificar->isNotEmpty()) {
            $this->error('Hay tablas sin clasificar. Agregalas al comando antes de seguir:');
            $sinClasificar->each(fn ($t) => $this->line("   - {$t}"));

            return self::FAILURE;
        }

        if ($inexistentes->isNotEmpty()) {
            $this->warn('Tablas listadas en el comando que no existen en esta base (se ignoran):');
            $inexistentes->each(fn ($t) => $this->line("   - {$t}"));
        }

        $this->mostrarPlan();

        if (! $this->option('ejecutar')) {
            $this->line('');
            $this->warn('Simulación: no se tocó nada. Corré de nuevo con --ejecutar para aplicarlo.');

            return self::SUCCESS;
        }

        $nombreEntidad = trim((string) $this->option('entidad'));
        if ($nombreEntidad === '') {
            $this->error('Para ejecutar hace falta --entidad="Nombre del centro nuevo".');

            return self::FAILURE;
        }

        $this->vaciar();
        $this->despersonalizar($nombreEntidad);
        $this->soltarReferenciasColgadas();

        $this->line('');
        $this->info('Listo: catálogos, permisos y plantillas, sin datos del centro original.');
        $this->line('Siguiente paso, crear el administrador:');
        $this->line('  php artisan db:seed --class=Database\\Seeders\\UserSeeder');

        return self::SUCCESS;
    }

    private function mostrarPlan(): void
    {
        $contar = fn (string $t) => Schema::hasTable($t) ? DB::table($t)->count() : null;

        $this->line('');
        $this->line('<comment>SE VACÍAN</comment> (datos del centro original):');
        foreach (self::VACIAR as $tabla) {
            $filas = $contar($tabla);
            if ($filas === null) {
                continue;
            }
            $this->line(sprintf('   %-38s %s', $tabla, $filas > 0 ? "{$filas} filas" : 'ya vacía'));
        }

        $this->line('');
        $this->line('<comment>SE LIMPIAN</comment> (quedan, sin los datos del centro original):');
        foreach (self::LIMPIAR as $tabla) {
            $filas = $contar($tabla);
            if ($filas !== null) {
                $this->line(sprintf('   %-38s %s filas', $tabla, $filas));
            }
        }

        $conservadas = collect(self::CONSERVAR)->filter(fn ($t) => $contar($t) !== null);
        $this->line('');
        $this->line('<info>SE CONSERVAN</info>: '.$conservadas->count().' tablas ('
            .$conservadas->take(6)->implode(', ').', …)');
    }

    private function vaciar(): void
    {
        $this->line('');
        $this->line('Vaciando…');

        Schema::disableForeignKeyConstraints();

        try {
            foreach (self::VACIAR as $tabla) {
                if (Schema::hasTable($tabla)) {
                    DB::table($tabla)->truncate();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function despersonalizar(string $nombreEntidad): void
    {
        $this->line('Despersonalizando lo que queda…');

        Schema::disableForeignKeyConstraints();

        try {
            // Una sola entidad principal: la del centro nuevo. Lo que se conserva
            // (actividades, clases, membresías, precios) se repunta a ella, o quedaría
            // colgado de sedes que ya no existen.
            DB::table('entidades')->delete();
            $entidadId = DB::table('entidades')->insertGetId([
                'nombre' => $nombreEntidad,
                'entidad_principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($this->tablasConEntidadId() as $tabla) {
                DB::table($tabla)->update(['entidad_id' => $entidadId]);
            }

            // Datos bancarios y links de pago del centro original.
            $this->blanquear('metodos_pago', 'descripcion');
            $this->blanquear('botones_pago', 'link');

            $this->line("   Entidad principal creada: [{$entidadId}] {$nombreEntidad}");
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Suelta las referencias que quedaron apuntando a filas borradas.
     *
     * Las tablas que se conservan referencian a varias de las que se vacían: una
     * actividad apunta a su imagen y a su stream, una clase a su maestro y a su
     * coordinador. Después del vaciado esos IDs señalan filas que ya no existen, y la
     * app se rompe recién cuando alguien abre esa pantalla.
     *
     * Se resuelve recorriendo las claves foráneas de la base en vez de enumerar los
     * casos a mano: así sigue funcionando cuando alguien agregue una tabla nueva.
     */
    private function soltarReferenciasColgadas(): void
    {
        $this->line('Soltando referencias a filas borradas…');

        $fks = DB::select(
            'SELECT table_name, column_name, referenced_table_name, referenced_column_name
             FROM information_schema.key_column_usage
             WHERE table_schema = DATABASE() AND referenced_table_name IS NOT NULL'
        );

        $sueltas = 0;

        foreach ($fks as $fk) {
            $fk = array_change_key_case((array) $fk, CASE_LOWER);
            $hijo = $fk['table_name'];
            $columna = $fk['column_name'];
            $padre = $fk['referenced_table_name'];
            $padreCol = $fk['referenced_column_name'];

            $huerfanos = DB::table("{$hijo} as h")
                ->leftJoin("{$padre} as p", "h.{$columna}", '=', "p.{$padreCol}")
                ->whereNotNull("h.{$columna}")
                ->whereNull("p.{$padreCol}")
                ->count();

            if ($huerfanos === 0) {
                continue;
            }

            $fila = DB::selectOne(
                'SELECT is_nullable AS acepta_null FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
                [$hijo, $columna]
            );

            if (! $fila || strtoupper((string) $fila->acepta_null) !== 'YES') {
                $this->error("   {$hijo}.{$columna} tiene {$huerfanos} referencias a {$padre} y NO acepta null: revisalo a mano.");

                continue;
            }

            DB::table("{$hijo} as h")
                ->leftJoin("{$padre} as p", "h.{$columna}", '=', "p.{$padreCol}")
                ->whereNotNull("h.{$columna}")
                ->whereNull("p.{$padreCol}")
                ->update(["h.{$columna}" => null]);

            $this->line(sprintf('   %-44s %d sueltas', "{$hijo}.{$columna} -> {$padre}", $huerfanos));
            $sueltas++;
        }

        if ($sueltas === 0) {
            $this->line('   No quedaba ninguna.');
        }
    }

    /**
     * Vacía una columna respetando si acepta null: varias de las que traen datos del
     * centro original (CBU en `metodos_pago.descripcion`, links en `botones_pago.link`)
     * son NOT NULL, y un update a null revienta.
     */
    private function blanquear(string $tabla, string $columna): void
    {
        if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
            return;
        }

        // Con alias explícito: MySQL 8 devuelve las columnas de information_schema en
        // mayúsculas y MariaDB en minúsculas.
        $fila = DB::selectOne(
            'SELECT is_nullable AS acepta_null FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$tabla, $columna]
        );

        DB::table($tabla)->update([
            $columna => ($fila && strtoupper((string) $fila->acepta_null) === 'YES') ? null : '',
        ]);
    }

    /** Tablas conservadas que apuntan a una entidad y hay que repuntar. @return list<string> */
    private function tablasConEntidadId(): array
    {
        return collect(self::CONSERVAR)
            ->merge(['actividad_metodo_pago'])
            ->filter(fn ($t) => Schema::hasTable($t) && Schema::hasColumn($t, 'entidad_id'))
            ->values()
            ->all();
    }
}
