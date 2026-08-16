<?php

namespace App\Services;

use App\Models\Cobro;
use App\Models\EstadoCuentaMembresia;
use App\Models\Imagen;
use App\Models\Inscripcion;
use App\Models\InscripcionClase;
use App\Models\MetodoPago;
use App\Models\Moneda;
use Illuminate\Database\Eloquent\Model;

/**
 * Punto único de entrada para registrar cobros en el ledger polimórfico `cobros`
 * y mantener la caché de estado de pago de las entidades que la derivan.
 */
class CobroService
{
    /**
     * Registra un cobro sobre un cobrable (Inscripcion, InscripcionClase, EstadoCuentaMembresia, Venta)
     * y recalcula el estado de pago cacheado si la entidad lo deriva.
     */
    public function registrar(Model $cobrable, array $datos, bool $recalcular = true): Cobro
    {
        $cobro = $cobrable->cobros()->create([
            'monto' => $datos['monto'],
            'moneda_id' => $datos['moneda_id'] ?? $this->monedaDeCobrable($cobrable),
            'fecha_pago' => $datos['fecha_pago'] ?? null,
            'metodo_pago_id' => $datos['metodo_pago_id'] ?? null,
            'referencia' => $datos['referencia'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'registrado_por' => $datos['registrado_por'] ?? null,
            'origen' => $datos['origen'] ?? 'manual',
            'estado' => $datos['estado'] ?? Cobro::ESTADO_CONFIRMADO,
        ]);

        // Comprobantes (1:N). Acepta `comprobante_ids` (array de imagenes.id) o el
        // legacy `comprobante_id` (uno solo).
        $comprobanteIds = $datos['comprobante_ids']
            ?? (isset($datos['comprobante_id']) ? [$datos['comprobante_id']] : []);
        $this->sincronizarComprobantes($cobro, $comprobanteIds);

        // El admin puede fijar el estado de pago a mano; en ese caso se registra el
        // cobro sin recalcular (recalcular=false) para no pisar su elección.
        if ($recalcular) {
            $this->recalcularEstadoPago($cobrable);
        }

        return $cobro;
    }

    /**
     * Moneda en la que se cobra un cobrable, para no tener que pasarla desde cada
     * llamador (webhook de MP, checkout, admin, POS).
     *
     * Sólo las inscripciones a actividades son multi-moneda: clases, membresías y
     * ventas se cobran siempre en la principal. Una inscripción sin `moneda_id`
     * (legacy) también es principal, según la convención de BUSINESS_RULES §2.1bis.
     */
    public function monedaDeCobrable(Model $cobrable): ?int
    {
        if ($cobrable instanceof Inscripcion && $cobrable->moneda_id) {
            return (int) $cobrable->moneda_id;
        }

        return Moneda::principalId();
    }

    /**
     * Porciones adeudadas por moneda: `[monedaId => monto]`.
     *
     * Una inscripción con total dividido (BUSINESS_RULES §2.1bis) debe dos cosas
     * distintas —p. ej. USD 120 de actividad + $ 2.000 de servicios— y cada una se
     * salda por separado, como ya hace el POS. El resto de los cobrables (clases,
     * membresías, ventas) tiene una sola moneda y devuelve una sola entrada.
     *
     * La clave 0 sólo aparece si la base no tiene moneda principal definida.
     */
    public function porcionesAdeudadas(Model $cobrable): array
    {
        $principalId = Moneda::principalId() ?? 0;

        if ($cobrable instanceof Inscripcion) {
            $monedaId = (int) ($cobrable->moneda_id ?: $principalId);
            $porcionPrincipal = round((float) ($cobrable->monto_moneda_principal ?? 0), 2);

            if ($monedaId !== $principalId && $porcionPrincipal > 0) {
                return [
                    $monedaId => round((float) $cobrable->montoapagar, 2),
                    $principalId => $porcionPrincipal,
                ];
            }
        }

        $moneda = (int) ($this->monedaDeCobrable($cobrable) ?: $principalId);

        return [$moneda => round((float) $cobrable->totalAdeudado(), 2)];
    }

    /**
     * Saldo pendiente por moneda: porción adeudada menos los cobros confirmados de
     * esa moneda. Sólo devuelve las monedas que todavía deben algo, así el llamador
     * puede iterarlo y emitir un cobro por cada una.
     */
    public function saldoPendientePorMoneda(Model $cobrable): array
    {
        $principalId = Moneda::principalId() ?? 0;

        $cobrado = $cobrable->cobros()->confirmados()->get(['moneda_id', 'monto'])
            ->groupBy(fn (Cobro $cobro) => (int) ($cobro->moneda_id ?: $principalId))
            ->map(fn ($grupo) => (float) $grupo->sum('monto'));

        $saldos = [];
        foreach ($this->porcionesAdeudadas($cobrable) as $monedaId => $adeudado) {
            $saldo = round($adeudado - (float) ($cobrado[$monedaId] ?? 0), 2);
            if ($saldo > 0) {
                $saldos[$monedaId] = $saldo;
            }
        }

        return $saldos;
    }

    /**
     * Cobros del cobrable acotados a una moneda. Los cobros legacy sin `moneda_id`
     * cuentan como de la principal (misma convención que en toda la app).
     */
    private function cobrosDeMoneda(Model $cobrable, ?int $monedaId)
    {
        $principalId = Moneda::principalId();
        $query = $cobrable->cobros();

        if ($monedaId && $principalId && (int) $monedaId === (int) $principalId) {
            return $query->where(fn ($q) => $q->where('moneda_id', $monedaId)->orWhereNull('moneda_id'));
        }

        return $monedaId ? $query->where('moneda_id', $monedaId) : $query->whereNull('moneda_id');
    }

    /** Saldo pendiente del cobrable en una sola moneda (0 si esa moneda ya está cubierta). */
    private function saldoDeMoneda(Model $cobrable, ?int $monedaId): float
    {
        $clave = (int) ($monedaId ?: (Moneda::principalId() ?? 0));

        return (float) ($this->saldoPendientePorMoneda($cobrable)[$clave] ?? 0);
    }

    /**
     * Reemplaza el set de comprobantes de un cobro por los `imagenes.id` dados
     * (filtra nulos y duplicados). Idempotente.
     */
    public function sincronizarComprobantes(Cobro $cobro, array $imagenIds): void
    {
        $ids = array_values(array_unique(array_filter($imagenIds)));

        $cobro->comprobantes()->delete();
        foreach ($ids as $imagenId) {
            $cobro->comprobantes()->create(['imagen_id' => $imagenId]);
        }
    }

    /**
     * Agrega comprobantes a un cobro sin tocar los que ya tiene (a diferencia de
     * sincronizarComprobantes, que reemplaza el set completo).
     */
    public function agregarComprobantes(Cobro $cobro, array $imagenIds, ?string $descripcion = null): void
    {
        $ids = array_values(array_unique(array_filter($imagenIds)));
        $existentes = $cobro->comprobantes()->pluck('imagen_id')->all();

        foreach (array_diff($ids, $existentes) as $imagenId) {
            $cobro->comprobantes()->create([
                'imagen_id' => $imagenId,
                'descripcion' => $descripcion,
            ]);
        }
    }

    /**
     * Registra un comprobante informado por el usuario como cobro `a_revisar`
     * (nunca hay comprobante sin cobro). El monto es provisional (saldo pendiente
     * de esa moneda al momento de la subida) y NO suma al saldo hasta que el admin
     * lo confirme. Invariante: a lo sumo un cobro a revisar por cobrable Y moneda —
     * una segunda subida en la misma moneda agrega el comprobante al existente. Si
     * esa moneda ya está saldada, el comprobante documenta el cobro confirmado
     * existente (no inventa deuda).
     *
     * `$monedaId` sale por defecto de la moneda del cobrable; sólo hace falta
     * pasarlo para saldar la porción en pesos de una inscripción dividida.
     */
    public function registrarComprobanteARevisar(
        Model $cobrable,
        int $imagenId,
        ?string $descripcion = null,
        string $origen = 'checkout',
        ?int $registradoPor = null,
        ?int $monedaId = null
    ): Cobro {
        $monedaId = $monedaId ?: $this->monedaDeCobrable($cobrable);
        $saldo = $this->saldoDeMoneda($cobrable, $monedaId);

        $aRevisar = $this->cobrosDeMoneda($cobrable, $monedaId)->aRevisar()->latest('id')->first();
        if ($aRevisar) {
            $this->agregarComprobantes($aRevisar, [$imagenId], $descripcion);
            $aRevisar->update(['monto' => max(0, $saldo)]);

            return $aRevisar;
        }

        if ($saldo <= 0) {
            $confirmado = $this->cobrosDeMoneda($cobrable, $monedaId)->confirmados()->latest('id')->first();
            if ($confirmado) {
                $this->agregarComprobantes($confirmado, [$imagenId], $descripcion);

                return $confirmado;
            }
        }

        $cobro = $this->registrar($cobrable, [
            'monto' => max(0, $saldo),
            'moneda_id' => $monedaId,
            'fecha_pago' => null,
            'origen' => $origen,
            'estado' => Cobro::ESTADO_A_REVISAR,
            'registrado_por' => $registradoPor,
        ], recalcular: false);

        $this->agregarComprobantes($cobro, [$imagenId], $descripcion);

        return $cobro;
    }

    /**
     * Punto de entrada del flujo admin (marcar Saldado/Parcial): si hay un cobro
     * a revisar EN ESA MONEDA lo CONFIRMA con los datos reales (monto/fecha/método/
     * registrador, origen y comprobantes intactos) en vez de crear uno nuevo. Sin
     * cobros a revisar, se comporta como registrar() (sólo si monto > 0). Con
     * monto <= 0 (ej. el saldo ya se cubrió por MP) el pendiente se cierra sin
     * duplicar plata: sus comprobantes pasan al último cobro confirmado de la misma
     * moneda y se soft-deletea.
     *
     * Todo va acotado a una moneda: confirmar la porción en dólares de una
     * inscripción dividida no puede tocar el comprobante informado por la de pesos.
     */
    public function confirmarORegistrar(Model $cobrable, array $datos): ?Cobro
    {
        $monto = (float) ($datos['monto'] ?? 0);
        $monedaId = isset($datos['moneda_id']) && $datos['moneda_id']
            ? (int) $datos['moneda_id']
            : $this->monedaDeCobrable($cobrable);
        $datos['moneda_id'] = $monedaId;

        $pendientes = $this->cobrosDeMoneda($cobrable, $monedaId)->aRevisar()->orderByDesc('id')->get();

        if ($pendientes->isEmpty()) {
            if ($monto <= 0) {
                return null;
            }

            return $this->registrar($cobrable, $datos, recalcular: false);
        }

        // Defensa por si el invariante "un solo a_revisar por moneda" se violó: los
        // extras vuelcan sus comprobantes en el principal y se dan de baja.
        $principal = $pendientes->first();
        foreach ($pendientes->slice(1) as $extra) {
            $this->agregarComprobantes($principal, $extra->comprobantes()->pluck('imagen_id')->all());
            $extra->delete();
        }

        if ($monto <= 0) {
            $confirmado = $this->cobrosDeMoneda($cobrable, $monedaId)->confirmados()->latest('id')->first();
            if ($confirmado) {
                $this->agregarComprobantes($confirmado, $principal->comprobantes()->pluck('imagen_id')->all());
            }
            $principal->delete();

            return $confirmado;
        }

        $principal->update([
            'monto' => $monto,
            'fecha_pago' => $datos['fecha_pago'] ?? now()->toDateString(),
            'metodo_pago_id' => $datos['metodo_pago_id'] ?? $principal->metodo_pago_id,
            'referencia' => $datos['referencia'] ?? $principal->referencia,
            'registrado_por' => $datos['registrado_por'] ?? $principal->registrado_por,
            'estado' => Cobro::ESTADO_CONFIRMADO,
        ]);

        return $principal;
    }

    /**
     * Recalcula la caché de estado de pago del cobrable a partir de sus cobros.
     * Inscripciones (actividades y clases): enum `pago` (Saldado/Parcial/Pendiente).
     * Membresías: delega en recalcularMembresia (el cobro es la fuente de verdad).
     * Ventas no derivan estado.
     */
    public function recalcularEstadoPago(Model $cobrable): void
    {
        if ($cobrable instanceof EstadoCuentaMembresia) {
            $this->recalcularMembresia($cobrable);

            return;
        }

        if (!($cobrable instanceof Inscripcion) && !($cobrable instanceof InscripcionClase)) {
            return;
        }

        // Saldado sólo cuando NINGUNA moneda debe nada: una inscripción dividida
        // (USD 120 + $ 2.000) no está saldada por cubrir una sola de las dos, y
        // comparar la suma de ambas contra la suma de los cobros acertaría apenas
        // por coincidencia aritmética.
        $cobrado = $cobrable->montoCobrado();
        $pendientes = $this->saldoPendientePorMoneda($cobrable);

        if (empty($pendientes)) {
            $pago = 'Saldado';
        } elseif ($cobrado <= 0.001) {
            $pago = 'Pendiente';
        } else {
            $pago = 'Parcial';
        }

        $cobrable->pago = $pago;

        // 'estado' solo existe en actividades y solo se promueve (nunca se degrada).
        if ($cobrable instanceof Inscripcion && $pago === 'Saldado') {
            $cobrable->estado = 'Confirmada';
        }

        $cobrable->save();
    }

    /**
     * Recomputa la caché de pago de una cuota de membresía DESDE su cobro (fuente de verdad):
     * `pagado` = existe un cobro. Cuando hay cobro, `fecha_pago`/`info_pago` y `modo` (si el
     * método resuelve) se derivan de él y el comprobante stageado se adjunta al cobro.
     * Sin cobro NO se limpian fecha/modo/info: pueden ser metadata de un pago informado y
     * pendiente de aprobación (comprobante subido con `pagado=false`).
     */
    public function recalcularMembresia(EstadoCuentaMembresia $cuota): void
    {
        $cobro = $cuota->cobros()->confirmados()->orderByDesc('fecha_pago')->orderByDesc('id')->first();

        $cuota->pagado = (bool) $cobro;

        if ($cobro) {
            $cuota->fecha_pago = $cobro->fecha_pago;
            $cuota->info_pago = $cobro->referencia;
            // 'modo' se deriva del método del cobro sólo si resuelve (no nulea un modo válido).
            if ($cobro->metodo_pago_id) {
                $cuota->modo = $cobro->metodoPago?->nombre ?: $cuota->modo;
            }
            if ($cuota->comprobante_imagen_id) {
                $this->sincronizarComprobantes($cobro, [$cuota->comprobante_imagen_id]);
            }
        }

        $cuota->save();
    }

    /**
     * Aplica los datos de pago de la cuota al ledger y recomputa su caché.
     * Si la cuota está marcada como pagada, crea/actualiza su cobro (uno por cuota, origen
     * `membresia`); si no, lo da de baja (soft-delete). Luego `recalcularMembresia` deja la
     * caché de la cuota derivada del cobro (el cobro es la fuente de verdad).
     */
    public function sincronizarMembresia(EstadoCuentaMembresia $cuota): void
    {
        if ($cuota->pagado) {
            $confirmado = $cuota->cobros()->updateOrCreate(
                ['origen' => 'membresia'],
                [
                    'monto' => (float) $cuota->importe,
                    'moneda_id' => $this->monedaDeCobrable($cuota),
                    'fecha_pago' => $cuota->fecha_pago,
                    'metodo_pago_id' => $this->resolverMetodoPago($cuota->modo),
                    'referencia' => $cuota->info_pago ?: null,
                    'observaciones' => $cuota->observaciones ?: null,
                    'estado' => Cobro::ESTADO_CONFIRMADO,
                ]
            );

            // El pago informado por el socio queda aprobado: sus comprobantes pasan
            // al cobro confirmado y el pendiente se da de baja (no se duplica plata).
            foreach ($cuota->cobros()->aRevisar()->get() as $pendiente) {
                $this->agregarComprobantes($confirmado, $pendiente->comprobantes()->pluck('imagen_id')->all());
                $pendiente->delete();
            }
        } else {
            // Sólo los confirmados: un cobro `a_revisar` es un pago que el socio
            // informó y todavía no se aprobó, y borrarlo acá perdería su comprobante.
            $cuota->cobros()->confirmados()->delete();

            // Comprobante informado sin aprobar → cobro a revisar, igual que en las
            // inscripciones: nunca hay comprobante sin cobro.
            if ($cuota->comprobante_imagen_id) {
                $this->registrarComprobanteARevisar($cuota, (int) $cuota->comprobante_imagen_id, null, 'membresia');
            }
        }

        $this->recalcularMembresia($cuota);
    }

    /**
     * Mapea un "modo" de texto (ventas.modo, estado_cuenta_membresias.modo) a un metodo_pago_id
     * del catálogo, por nombre normalizado (case/acento-insensible). Devuelve null si no matchea.
     */
    public function resolverMetodoPago(?string $modo): ?int
    {
        $modo = trim((string) $modo);
        if ($modo === '') {
            return null;
        }

        $buscado = $this->normalizar($modo);

        return MetodoPago::all(['id', 'nombre'])
            ->first(fn (MetodoPago $m) => $this->normalizar((string) $m->nombre) === $buscado)?->id;
    }

    /**
     * Convierte un path de comprobante (string legacy) en una fila `imagenes` reutilizable
     * y devuelve su id, para enlazarlo a `cobros.comprobante_id`. `firstOrCreate` por ruta
     * evita duplicar la imagen si el mismo path se sincroniza más de una vez.
     */
    public function resolverComprobanteId(?string $path): ?int
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        return Imagen::firstOrCreate(
            ['ruta' => $path],
            ['nombre' => basename($path)]
        )->id;
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');

        return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
    }
}
