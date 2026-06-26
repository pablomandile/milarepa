<?php

namespace App\Services;

use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

/**
 * Encapsula la integración con Mercado Pago Checkout Pro.
 *
 * Diseñado para reutilizarse (inscripciones a actividades hoy; membresías/clases
 * más adelante): crea preferencias de pago, consulta el estado real de un pago y
 * valida la firma de los webhooks.
 */
class MercadoPagoService
{
    public function __construct()
    {
        $accessToken = (string) config('services.mercadopago.access_token');
        MercadoPagoConfig::setAccessToken($accessToken);
    }

    public function estaConfigurado(): bool
    {
        return !empty(config('services.mercadopago.access_token'));
    }

    /**
     * Crea una preferencia de Checkout Pro para una inscripción y devuelve la URL
     * del checkout a la que hay que redirigir al comprador.
     */
    public function crearPreferencia(Inscripcion $inscripcion): string
    {
        $inscripcion->loadMissing(['actividad', 'user', 'guestUser']);

        $pagador = $inscripcion->guestUser ?: $inscripcion->user;
        $monto = round((float) $inscripcion->montoapagar, 2);
        $titulo = $inscripcion->actividad?->nombre ?: 'Inscripción';

        $request = [
            'items' => [[
                'id' => (string) $inscripcion->id,
                'title' => mb_substr($titulo, 0, 250),
                'quantity' => 1,
                'unit_price' => $monto,
                'currency_id' => 'ARS',
            ]],
            'payer' => array_filter([
                'name' => $pagador?->name,
                'email' => $pagador?->email,
            ]),
            'external_reference' => (string) $inscripcion->id,
            'back_urls' => [
                'success' => $this->backUrl($inscripcion),
                'pending' => $this->backUrl($inscripcion),
                'failure' => $this->backUrl($inscripcion),
            ],
            'auto_return' => 'approved',
            'notification_url' => $this->notificationUrl(),
        ];

        try {
            $preference = (new PreferenceClient())->create($request);
        } catch (MPApiException $e) {
            Log::error('Mercado Pago: error al crear preferencia', [
                'inscripcion_id' => $inscripcion->id,
                'status' => $e->getApiResponse()?->getStatusCode(),
                'content' => $e->getApiResponse()?->getContent(),
            ]);
            throw $e;
        }

        $accessToken = (string) config('services.mercadopago.access_token');
        if (str_starts_with($accessToken, 'TEST-') && !empty($preference->sandbox_init_point)) {
            return $preference->sandbox_init_point;
        }

        return $preference->init_point;
    }

    /**
     * Consulta el estado real de un pago en la API de Mercado Pago.
     * (No confiar en los query params del retorno: son manipulables.)
     */
    public function obtenerPago(string $paymentId): ?object
    {
        try {
            return (new PaymentClient())->get($paymentId);
        } catch (MPApiException $e) {
            Log::error('Mercado Pago: error al obtener pago', [
                'payment_id' => $paymentId,
                'status' => $e->getApiResponse()?->getStatusCode(),
                'content' => $e->getApiResponse()?->getContent(),
            ]);

            return null;
        }
    }

    /**
     * Valida la firma del webhook (header x-signature) según el algoritmo de MP:
     * HMAC-SHA256 sobre "id:<data.id>;request-id:<x-request-id>;ts:<ts>;".
     */
    public function validarFirmaWebhook(Request $request): bool
    {
        $secret = (string) config('services.mercadopago.webhook_secret');
        if ($secret === '') {
            Log::warning('Mercado Pago: webhook sin MP_WEBHOOK_SECRET configurado; no se puede validar la firma.');

            return false;
        }

        $xSignature = (string) $request->header('x-signature', '');
        $xRequestId = (string) $request->header('x-request-id', '');
        if ($xSignature === '' || $xRequestId === '') {
            return false;
        }

        $partes = [];
        foreach (explode(',', $xSignature) as $segmento) {
            $kv = explode('=', $segmento, 2);
            if (count($kv) === 2) {
                $partes[trim($kv[0])] = trim($kv[1]);
            }
        }

        $ts = $partes['ts'] ?? null;
        $v1 = $partes['v1'] ?? null;
        if (!$ts || !$v1) {
            return false;
        }

        $dataId = $this->extraerDataId($request);
        // MP indica usar el data.id en minúsculas cuando es alfanumérico.
        $dataId = strtolower((string) $dataId);

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $hash = hash_hmac('sha256', $manifest, $secret);

        $valido = hash_equals($hash, $v1);

        // DEBUG TEMPORAL — quitar una vez validado el webhook.
        Log::info('MP webhook debug firma', [
            'manifest' => $manifest,
            'data_id' => $dataId,
            'ts' => $ts,
            'v1_recibido' => $v1,
            'hash_calculado' => $hash,
            'coincide' => $valido,
            'secret_len' => strlen($secret),
            'query_keys' => array_keys($request->query()),
            'body_keys' => array_keys($request->all()),
        ]);

        return $valido;
    }

    /**
     * Obtiene el data.id de la notificación. En la query MP envía "data.id", que
     * PHP convierte a "data_id"; también puede venir en el body JSON anidado.
     */
    public function extraerDataId(Request $request): ?string
    {
        $id = $request->query('data_id')
            ?? $request->input('data.id')
            ?? $request->query('id')
            ?? $request->input('id');

        return $id !== null ? (string) $id : null;
    }

    private function backUrl(Inscripcion $inscripcion): string
    {
        return route('grid-actividades.pago.retorno', ['inscripcion' => $inscripcion->id]);
    }

    private function notificationUrl(): string
    {
        $override = (string) config('services.mercadopago.webhook_url');

        return $override !== '' ? $override : route('mercadopago.webhook');
    }
}
