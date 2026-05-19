<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Token opaco devuelto por GridActividadesController::lookupEmail.
 *
 * Reemplaza el id numérico del usuario en respuestas a anónimos para que
 * preparePago y subscribePublic no acepten user_id arbitrarios (vector IDOR).
 *
 * El payload es cifrado con APP_KEY (Crypt::encryptString) → no se puede
 * falsificar ni leer sin la key. TTL embebido: si vence, el endpoint
 * consumidor responde 422 y el frontend pide re-verificar el email.
 */
class UserLookupToken
{
    /** Tiempo de vida del token en minutos. */
    public const TTL_MINUTES = 15;

    /**
     * Genera un token cifrado para el user_id dado.
     */
    public static function issue(int $userId): string
    {
        return Crypt::encryptString(json_encode([
            'user_id' => $userId,
            'exp' => now()->addMinutes(self::TTL_MINUTES)->timestamp,
        ]));
    }

    /**
     * Decodifica el token. Devuelve el user_id válido o null si está
     * vencido, malformado o no descifrable con APP_KEY.
     */
    public static function resolveUserId(?string $token): ?int
    {
        if (!$token) {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return null;
        }

        if (!is_array($payload) || empty($payload['user_id']) || empty($payload['exp'])) {
            return null;
        }

        if ($payload['exp'] < now()->timestamp) {
            return null;
        }

        return (int) $payload['user_id'];
    }
}
