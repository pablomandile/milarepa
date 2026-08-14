<?php

namespace App\Services;

use App\Models\Cobro;
use App\Models\EstadoCuentaMembresia;
use App\Models\Imagen;
use App\Models\Inscripcion;
use App\Models\InscripcionClase;
use App\Models\MetodoPago;
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
            'moneda_id' => $datos['moneda_id'] ?? null,
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
     * al momento de la subida) y NO suma al saldo hasta que el admin lo confirme.
     * Invariante: a lo sumo un cobro a revisar por cobrable — una segunda subida
     * agrega el comprobante al existente. Si la deuda ya está saldada, el
     * comprobante documenta el cobro confirmado existente (no inventa deuda).
     */
    public function registrarComprobanteARevisar(
        Model $cobrable,
        int $imagenId,
        ?string $descripcion = null,
        string $origen = 'checkout',
        ?int $registradoPor = null
    ): Cobro {
        $aRevisar = $cobrable->cobros()->aRevisar()->latest('id')->first();
        if ($aRevisar) {
            $this->agregarComprobantes($aRevisar, [$imagenId], $descripcion);
            $aRevisar->update(['monto' => max(0, $cobrable->saldoPendiente())]);

            return $aRevisar;
        }

        if ($cobrable->saldoPendiente() <= 0) {
            $confirmado = $cobrable->cobros()->confirmados()->latest('id')->first();
            if ($confirmado) {
                $this->agregarComprobantes($confirmado, [$imagenId], $descripcion);

                return $confirmado;
            }
        }

        $cobro = $this->registrar($cobrable, [
            'monto' => max(0, $cobrable->saldoPendiente()),
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
     * a revisar lo CONFIRMA con los datos reales (monto/fecha/método/registrador,
     * origen y comprobantes intactos) en vez de crear uno nuevo. Sin cobros a
     * revisar, se comporta como registrar() (sólo si monto > 0). Con monto <= 0
     * (ej. el saldo ya se cubrió por MP) el pendiente se cierra sin duplicar plata:
     * sus comprobantes pasan al último cobro confirmado y se soft-deletea.
     */
    public function confirmarORegistrar(Model $cobrable, array $datos): ?Cobro
    {
        $monto = (float) ($datos['monto'] ?? 0);

        $pendientes = $cobrable->cobros()->aRevisar()->orderByDesc('id')->get();

        if ($pendientes->isEmpty()) {
            if ($monto <= 0) {
                return null;
            }

            return $this->registrar($cobrable, $datos, recalcular: false);
        }

        // Defensa por si el invariante "un solo a_revisar" se violó: los extras
        // vuelcan sus comprobantes en el principal y se dan de baja.
        $principal = $pendientes->first();
        foreach ($pendientes->slice(1) as $extra) {
            $this->agregarComprobantes($principal, $extra->comprobantes()->pluck('imagen_id')->all());
            $extra->delete();
        }

        if ($monto <= 0) {
            $confirmado = $cobrable->cobros()->confirmados()->latest('id')->first();
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

        $total = (float) $cobrable->totalAdeudado();
        $cobrado = $cobrable->montoCobrado();

        if ($total <= 0 || $cobrado >= $total - 0.001) {
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
            $cuota->cobros()->updateOrCreate(
                ['origen' => 'membresia'],
                [
                    'monto' => (float) $cuota->importe,
                    'fecha_pago' => $cuota->fecha_pago,
                    'metodo_pago_id' => $this->resolverMetodoPago($cuota->modo),
                    'referencia' => $cuota->info_pago ?: null,
                    'observaciones' => $cuota->observaciones ?: null,
                ]
            );
        } else {
            // OJO si membresías adopta cobros a_revisar: este delete arrasaría
            // también los cobros en revisión (hoy las cuotas sólo tienen confirmados).
            $cuota->cobros()->delete();
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
