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
            'comprobante_id' => $datos['comprobante_id'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'registrado_por' => $datos['registrado_por'] ?? null,
            'origen' => $datos['origen'] ?? 'manual',
        ]);

        // El admin puede fijar el estado de pago a mano; en ese caso se registra el
        // cobro sin recalcular (recalcular=false) para no pisar su elección.
        if ($recalcular) {
            $this->recalcularEstadoPago($cobrable);
        }

        return $cobro;
    }

    /**
     * Recalcula el enum `pago` (caché) a partir de la suma de cobros vs el total adeudado.
     * Solo aplica a inscripciones (actividades y clases); ventas y membresías no derivan estado.
     */
    public function recalcularEstadoPago(Model $cobrable): void
    {
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
     * Espejo UNIDIRECCIONAL de una cuota de membresía en el ledger.
     * Si la cuota está pagada, crea/actualiza su cobro espejo (uno por cuota);
     * si no, lo da de baja (soft-delete). Crear/editar un cobro NUNCA modifica la cuota.
     */
    public function sincronizarMembresia(EstadoCuentaMembresia $cuota): void
    {
        if (!$cuota->pagado) {
            $cuota->cobros()->delete();

            return;
        }

        $cuota->cobros()->updateOrCreate(
            ['origen' => 'membresia'],
            [
                'monto' => (float) $cuota->importe,
                'fecha_pago' => $cuota->fecha_pago,
                'metodo_pago_id' => $this->resolverMetodoPago($cuota->modo),
                'referencia' => $cuota->info_pago ?: null,
                'observaciones' => $cuota->observaciones ?: null,
                'comprobante_id' => $this->resolverComprobanteId($cuota->comprobante),
            ]
        );
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
