<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\User;
use Carbon\Carbon;

/**
 * Lógica de precios de actividades (esquema vigente por membresía / descuento anticipado,
 * modalidad online, estado según monto). Extraída para que el POS pueda cotizar/crear
 * inscripciones a actividad reutilizando las mismas reglas del checkout público.
 *
 * Nota: el checkout público (GridActividadesController) conserva su propia copia de estos
 * helpers por ahora, para no alterar ese flujo (dinero + MercadoPago). Unificarlo (delegar
 * el controller a este servicio) es un follow-up seguro una vez verificado el flujo público.
 */
class PrecioActividadService
{
    /** @return array{0: float, 1: float, 2: string} [precioGeneral, precioMembresia, membresiaNombre] */
    public function calcularPrecios(Actividad $actividad, ?User $user, ?int $monedaId = null): array
    {
        $actividad->loadMissing([
            'esquemaPrecio.membresias.membresia',
            'esquemaPrecio.membresias.moneda',
            'esquemaDescuento.membresias.membresia',
            'esquemaDescuento.membresias.moneda',
        ]);

        $precioGeneral = 0;
        $precioMembresia = 0;
        $membresiaNombre = 'Sin membresía';
        $esquemaVigente = $this->esquemaVigente($actividad);

        if ($esquemaVigente?->membresias) {
            $general = $this->resolverLineaEsquema($esquemaVigente->membresias, null, $monedaId)
                ?? $esquemaVigente->membresias->first(fn ($linea) => $this->esMembresiaGeneral($linea?->membresia?->nombre));
            $precioGeneral = $general->precio ?? 0;
        }

        if ($user && $user->membresia_id && $esquemaVigente?->membresias) {
            $pivot = $this->resolverLineaEsquema($esquemaVigente->membresias, (int) $user->membresia_id, $monedaId)
                ?? $esquemaVigente->membresias->firstWhere('membresia_id', $user->membresia_id);
            $precioMembresia = $pivot->precio ?? 0;
            $membresiaNombre = $user->membresia?->nombre ?? $membresiaNombre;
        }

        return [(float) $precioGeneral, (float) $precioMembresia, $membresiaNombre];
    }

    public function esquemaVigente(Actividad $actividad)
    {
        if ($this->aplicaDescuentoAnticipado($actividad) && $actividad->esquemaDescuento) {
            return $actividad->esquemaDescuento;
        }

        return $actividad->esquemaPrecio;
    }

    public function aplicaDescuentoAnticipado(Actividad $actividad): bool
    {
        if (empty($actividad->pagoAmticipado)) {
            return false;
        }

        try {
            $limite = Carbon::parse($actividad->pagoAmticipado);
        } catch (\Exception $e) {
            return false;
        }

        return now()->lte($limite);
    }

    public function esMembresiaGeneral(?string $nombre): bool
    {
        $normalized = mb_strtolower(trim((string) $nombre), 'UTF-8');
        $normalized = strtr($normalized, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);

        return str_contains($normalized, 'sin membres');
    }

    public function resolverLineaEsquema($lineas, ?int $membresiaId, ?int $monedaId)
    {
        if (!$lineas || $lineas->isEmpty()) {
            return null;
        }

        if ($membresiaId && $monedaId) {
            $exacta = $lineas->first(function ($linea) use ($membresiaId, $monedaId) {
                return (int) ($linea->membresia_id ?? 0) === $membresiaId
                    && (int) ($linea->moneda_id ?? 0) === $monedaId;
            });
            if ($exacta) {
                return $exacta;
            }
        }

        if ($monedaId) {
            $generalMoneda = $lineas->first(function ($linea) use ($monedaId) {
                return (int) ($linea->moneda_id ?? 0) === $monedaId
                    && $this->esMembresiaGeneral($linea?->membresia?->nombre);
            });
            if ($generalMoneda) {
                return $generalMoneda;
            }
        }

        if ($membresiaId) {
            $membresiaCualquieraMoneda = $lineas->first(function ($linea) use ($membresiaId) {
                return (int) ($linea->membresia_id ?? 0) === $membresiaId;
            });
            if ($membresiaCualquieraMoneda) {
                return $membresiaCualquieraMoneda;
            }
        }

        return $lineas->first(fn ($linea) => $this->esMembresiaGeneral($linea?->membresia?->nombre))
            ?? $lineas->first();
    }

    public function resolverModalidadOnline(Actividad $actividad, ?User $user, bool $registrado, ?string $modalidadCursada): bool
    {
        $modalidad = $this->normalizarTextoModalidad($actividad->modalidad?->nombre);

        if ($modalidad === 'online') {
            return true;
        }

        $seleccion = strtolower((string) ($modalidadCursada ?? 'presencial'));
        if (!in_array($seleccion, ['presencial', 'online'], true)) {
            $seleccion = 'presencial';
        }

        if ($modalidad === 'presencial y online abierta') {
            return $seleccion === 'online';
        }

        if ($modalidad === 'presencial y online') {
            $puedeElegirOnline = $registrado && (bool) ($user?->membresia_online ?? false);
            return $puedeElegirOnline && $seleccion === 'online';
        }

        return false;
    }

    public function normalizarTextoModalidad(?string $texto): string
    {
        $normalized = mb_strtolower(trim((string) $texto), 'UTF-8');

        return strtr($normalized, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
    }

    /** @return array{0: string, 1: string} [pago, estado] */
    public function resolverEstadoSegunMonto(float $montoApagar): array
    {
        if ($montoApagar <= 0.0) {
            return ['Saldado', 'Confirmada'];
        }

        return ['Pendiente', 'Registrada'];
    }
}
