<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Services\InscripcionMailService;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recibe las notificaciones (webhooks) de Mercado Pago y confirma el pago de la
 * inscripción correspondiente. Es la fuente de verdad del estado del pago: el
 * estado real se consulta SIEMPRE contra la Payment API (no por query params).
 *
 * Ruta pública, sin auth y exenta de CSRF (ver VerifyCsrfToken::$except).
 */
class MercadoPagoWebhookController extends Controller
{
    public function handle(Request $request, MercadoPagoService $mercadoPago, InscripcionMailService $mailService)
    {
        // 1. Validar la firma del webhook (HMAC con MP_WEBHOOK_SECRET).
        if (!$mercadoPago->validarFirmaWebhook($request)) {
            Log::warning('Mercado Pago webhook: firma inválida o secret no configurado.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false], 401);
        }

        // 2. Solo procesamos notificaciones de tipo "payment".
        $tipo = $request->input('type')
            ?? $request->query('type')
            ?? $request->input('topic')
            ?? $request->query('topic');

        if ($tipo !== 'payment') {
            return response()->json(['ok' => true]); // merchant_order u otros: ignorar.
        }

        $paymentId = $mercadoPago->extraerDataId($request);
        if (empty($paymentId)) {
            return response()->json(['ok' => true]);
        }

        // 3. Consultar el estado real del pago en la API de MP.
        $payment = $mercadoPago->obtenerPago($paymentId);
        if (!$payment) {
            // No se pudo consultar: devolvemos 200 igual; MP reintentará la notificación.
            return response()->json(['ok' => true]);
        }

        if (($payment->status ?? null) !== 'approved') {
            return response()->json(['ok' => true]); // pendiente/rechazado: no confirmamos.
        }

        $inscripcionId = $payment->external_reference ?? null;
        if (empty($inscripcionId)) {
            return response()->json(['ok' => true]);
        }

        $inscripcion = Inscripcion::find((int) $inscripcionId);
        if (!$inscripcion) {
            Log::warning('Mercado Pago webhook: inscripción no encontrada.', [
                'external_reference' => $inscripcionId,
                'payment_id' => $paymentId,
            ]);

            return response()->json(['ok' => true]);
        }

        // 4. Idempotencia: si ya está saldada, no reprocesar (MP puede reenviar).
        if ($inscripcion->pago === 'Saldado') {
            return response()->json(['ok' => true]);
        }

        $inscripcion->update([
            'pago' => 'Saldado',
            'estado' => 'Confirmada',
            'fecha_pago' => now()->toDateString(),
            'referencia_pago' => (string) ($payment->id ?? $paymentId),
        ]);

        // 5. Mail de confirmación (recarga relaciones que usa la vista del mail).
        $inscripcion->load([
            'actividad.entidad',
            'actividad.imagen',
            'actividad.descripcion',
            'actividad.modalidad',
            'actividad.stream.links',
            'user',
            'guestUser',
        ]);
        $mailService->enviarConfirmacionRegistro($inscripcion);

        return response()->json(['ok' => true]);
    }
}
