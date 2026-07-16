<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\GuestUser;
use App\Models\Inscripcion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Crea una inscripción a actividad desde un payload explícito (para el POS), reutilizando
 * el cálculo de servicios/invitados/cupos (InscripcionServiciosService + HospedajeCupoService)
 * y los precios (PrecioActividadService). NO manda mails, NO MercadoPago, NO cobro
 * (eso lo hace el llamador). Resuelve el participante por email (o crea invitado).
 * El checkout público (GridActividadesController::finalizarPago) queda intacto.
 */
class InscripcionActividadService
{
    public function __construct(
        private InscripcionServiciosService $servicios,
        private HospedajeCupoService $cupo,
        private PrecioActividadService $precios,
    ) {
    }

    public function crearDesdePayload(array $p, ?int $vendedorId = null): Inscripcion
    {
        $actividad = Actividad::with([
            'modalidad',
            'esquemaPrecio.membresias.membresia',
            'esquemaPrecio.membresias.moneda',
            'esquemaDescuento.membresias.membresia',
            'esquemaDescuento.membresias.moneda',
            'grabacion',
            'comidas',
            'transportes',
            'hospedajes',
        ])->findOrFail($p['actividad_id']);

        $email = Str::lower(trim((string) ($p['email'] ?? '')));
        if ($email === '') {
            throw ValidationException::withMessages(['items' => 'Falta el email del participante para la inscripción a actividad.']);
        }
        $registrarDatos = (bool) ($p['registrar_datos'] ?? false);

        $user = User::with(['membresia', 'membresiaUsuario'])->where('email', $email)->first();
        $guestUser = null;
        $registrado = false;

        if ($user) {
            $registrado = true;
        } else {
            // Nota: no seteamos pais_id explícito (users.pais_id es NOT NULL DEFAULT 1;
            // pasar null viola la constraint). Solo lo incluimos si viene en el payload.
            $datos = [
                'name' => $p['nombre'] ?? 'Sin nombre',
                'email' => $email,
                'telefono' => $p['telefono'] ?? null,
                'provincia_id' => $p['provincia_id'] ?? null,
                'municipio_id' => $p['municipio_id'] ?? null,
                'barrio_id' => $p['barrio_id'] ?? null,
                'direccion' => $p['direccion'] ?? null,
            ];
            if (!empty($p['pais_id'])) {
                $datos['pais_id'] = (int) $p['pais_id'];
            }
            if ($registrarDatos) {
                $user = User::create($datos + ['password' => Hash::make(Str::random(24))]);
                $registrado = true;
            } else {
                $guestUser = GuestUser::create($datos);
                $user = $this->ensureGuestOwner();
            }
        }

        $yaInscripto = Inscripcion::query()
            ->where('actividad_id', $actividad->id)
            ->when($guestUser, fn ($q) => $q->where('guest_user_id', $guestUser->id), fn ($q) => $q->where('user_id', $user->id))
            ->exists();
        if ($yaInscripto) {
            throw ValidationException::withMessages(['items' => 'El participante ya está inscripto a esa actividad.']);
        }

        $monedaId = isset($p['moneda_id']) ? (int) $p['moneda_id'] : null;
        [$precioGeneral, $precioMembresia, $membresiaNombre] = $this->precios->calcularPrecios($actividad, $registrado ? $user : null, $monedaId);
        $montoActividad = (float) (($user && $user->membresia_id) ? $precioMembresia : $precioGeneral);
        $online = $this->precios->resolverModalidadOnline($actividad, $user, $registrado, $p['modalidad_cursada'] ?? null);
        $incluyeGrabacion = (bool) ($p['incluye_grabacion'] ?? false);
        $envioLinkStream = $actividad->stream_id ? 'Pendiente' : 'No aplica';
        $envioGrabacion = $actividad->grabacion_id ? 'Pendiente' : 'No aplica';

        $comidasIds = $p['comidas_ids'] ?? [];
        $transportesIds = $p['transportes_ids'] ?? [];
        $hospedajesIds = $p['hospedajes_ids'] ?? [];
        $comidaId = $comidasIds[0] ?? null;
        $transporteId = $transportesIds[0] ?? null;
        $hospedajeId = $hospedajesIds[0] ?? null;

        $montos = $this->servicios->montosServicios($actividad, $incluyeGrabacion, $comidasIds, $transportesIds, $hospedajesIds);
        $invitadosData = $this->servicios->prepararInvitados($actividad, (float) $precioGeneral, $p['invitados'] ?? []);
        $montoInvitados = array_sum(array_column($invitadosData, 'montoapagar'));
        $hospedajeRequeridos = $this->cupo->requeridos($hospedajesIds, $invitadosData);

        $montoApagar = $montoActividad
            + (float) ($montos['montoGrabacion'] ?? 0)
            + (float) ($montos['montoComidas'] ?? 0)
            + (float) ($montos['montoTransporte'] ?? 0)
            + (float) ($montos['montoHospedaje'] ?? 0)
            + $montoInvitados;
        [$estadoPago, $estadoInscripcion] = $this->precios->resolverEstadoSegunMonto($montoApagar);

        return DB::transaction(function () use (
            $actividad, $user, $guestUser, $hospedajeRequeridos, $membresiaNombre, $precioGeneral, $montoActividad,
            $montos, $montoApagar, $montoInvitados, $estadoPago, $estadoInscripcion, $envioLinkStream, $envioGrabacion,
            $online, $hospedajeId, $comidaId, $transporteId, $comidasIds, $invitadosData
        ) {
            $this->cupo->validar($actividad, $hospedajeRequeridos, null);

            $inscripcion = Inscripcion::create([
                'actividad_id' => $actividad->id,
                'user_id' => $user->id,
                'guest_user_id' => $guestUser?->id,
                'membresia' => $membresiaNombre,
                'precioGeneral' => $precioGeneral,
                'montoActividad' => $montoActividad,
                'montoGrabacion' => $montos['montoGrabacion'] ?? null,
                'montoTransporte' => $montos['montoTransporte'] ?? null,
                'montoComidas' => $montos['montoComidas'] ?? null,
                'montoapagar' => $montoApagar,
                'monto_invitados' => $montoInvitados,
                'pago' => $estadoPago,
                'estado' => $estadoInscripcion,
                'envioLinkStream' => $envioLinkStream,
                'envioRegistro' => 'Pendiente',
                'envioConfirmacion' => 'Pendiente',
                'envioGrabacion' => $envioGrabacion,
                'asistencia' => 'Pendiente',
                'online' => $online,
                'hospedaje_id' => $hospedajeId,
                'comida_id' => $comidaId,
                'transporte_id' => $transporteId,
            ]);

            if (!empty($comidasIds)) {
                $inscripcion->comidas()->sync($comidasIds);
            }

            $this->servicios->persistirInvitados($inscripcion, $invitadosData);

            return $inscripcion;
        });
    }

    /** Calcula los montos (sin persistir) para mostrar el subtotal en el POS. */
    public function cotizar(array $p): array
    {
        $actividad = Actividad::with([
            'modalidad',
            'esquemaPrecio.membresias.membresia',
            'esquemaPrecio.membresias.moneda',
            'esquemaDescuento.membresias.membresia',
            'esquemaDescuento.membresias.moneda',
            'grabacion',
            'comidas',
            'transportes',
            'hospedajes',
        ])->findOrFail($p['actividad_id']);

        $email = Str::lower(trim((string) ($p['email'] ?? '')));
        $user = $email !== '' ? User::with(['membresia', 'membresiaUsuario'])->where('email', $email)->first() : null;
        $registrado = (bool) $user;

        $monedaId = isset($p['moneda_id']) ? (int) $p['moneda_id'] : null;
        [$precioGeneral, $precioMembresia, $membresiaNombre] = $this->precios->calcularPrecios($actividad, $registrado ? $user : null, $monedaId);
        $montoActividad = (float) (($user && $user->membresia_id) ? $precioMembresia : $precioGeneral);

        $comidasIds = $p['comidas_ids'] ?? [];
        $transportesIds = $p['transportes_ids'] ?? [];
        $hospedajesIds = $p['hospedajes_ids'] ?? [];

        $montos = $this->servicios->montosServicios($actividad, (bool) ($p['incluye_grabacion'] ?? false), $comidasIds, $transportesIds, $hospedajesIds);
        $invitadosData = $this->servicios->prepararInvitados($actividad, (float) $precioGeneral, $p['invitados'] ?? []);
        $montoInvitados = array_sum(array_column($invitadosData, 'montoapagar'));

        $montoApagar = $montoActividad
            + (float) ($montos['montoGrabacion'] ?? 0)
            + (float) ($montos['montoComidas'] ?? 0)
            + (float) ($montos['montoTransporte'] ?? 0)
            + (float) ($montos['montoHospedaje'] ?? 0)
            + $montoInvitados;

        return [
            'membresia' => $membresiaNombre,
            'montoActividad' => round($montoActividad, 2),
            'montoGrabacion' => round((float) ($montos['montoGrabacion'] ?? 0), 2),
            'montoComidas' => round((float) ($montos['montoComidas'] ?? 0), 2),
            'montoTransporte' => round((float) ($montos['montoTransporte'] ?? 0), 2),
            'montoHospedaje' => round((float) ($montos['montoHospedaje'] ?? 0), 2),
            'montoInvitados' => round((float) $montoInvitados, 2),
            'montoApagar' => round($montoApagar, 2),
        ];
    }

    private function ensureGuestOwner(): User
    {
        return User::firstOrCreate(
            ['email' => 'guest@milarepa.local'],
            ['name' => 'Invitado', 'password' => Hash::make(Str::random(32))]
        );
    }
}
