<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            padding: 16px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 36px 28px;
            text-align: center;
        }
        .header h1 {
            font-size: 26px;
            margin-bottom: 8px;
            font-weight: 700;
        }
        .header p {
            font-size: 14px;
            opacity: 0.92;
        }
        .content {
            padding: 32px 28px;
        }
        .greeting {
            color: #667eea;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 18px;
        }
        .paragraph {
            font-size: 15px;
            color: #555;
            margin-bottom: 14px;
        }
        .price-box {
            background-color: #f0f4ff;
            border-left: 4px solid #667eea;
            border-radius: 8px;
            padding: 16px;
            margin: 18px 0;
        }
        .price-line {
            font-size: 16px;
            color: #2d3748;
            margin: 6px 0;
        }
        .price-line strong {
            color: #111827;
        }
        .notice {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
            color: #4b5563;
            font-size: 14px;
        }
        .payment-wrap {
            text-align: center;
            margin: 22px 0 10px;
        }
        .payment-wrap .payment-link {
            display: inline-block;
            background: #ffffff;
            border: 1px solid #dbe4ff;
            border-radius: 12px;
            padding: 10px 14px;
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.18);
            text-decoration: none;
        }
        .payment-wrap img {
            max-width: 182px;
            width: auto;
            height: auto;
            border: 0;
            display: block;
        }
        .closing {
            margin-top: 26px;
            font-size: 15px;
            color: #374151;
        }
        .big-message {
            margin-top: 24px;
            font-weight: 700;
            text-transform: uppercase;
            color: #4f46e5;
            text-align: center;
            font-size: 15px;
            letter-spacing: 0.4px;
        }
        .logo-wrap {
            text-align: center;
            margin-top: 26px;
        }
        .logo-wrap img {
            max-height: 72px;
            max-width: 260px;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 22px;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
@php
    if (is_object($inscripcion) && method_exists($inscripcion, 'loadMissing')) {
        $inscripcion->loadMissing([
            'user.membresia.botonPago.metodoPago.imagen',
            'user.membresia.entidad',
            'actividad.entidad',
            'actividad.metodosPago.imagen',
        ]);
    }

    $nombreUsuario = data_get($usuario, 'name', '');
    $membresia = data_get($inscripcion, 'user.membresia') ?: data_get($usuario, 'membresia');

    $membresiaDescripcion = data_get($membresia, 'descripcion')
        ?: data_get($membresia, 'nombre')
        ?: data_get($inscripcion, 'membresia', 'Membresía');

    $membresiaValor = data_get($membresia, 'valor', data_get($inscripcion, 'montoActividad', 0));
    $membresiaValorNumero = is_numeric($membresiaValor) ? (float) $membresiaValor : 0;

    $botonPagoLink = data_get($membresia, 'botonPago.link');
    $metodoPagoNombre = data_get($membresia, 'botonPago.metodoPago.nombre', 'Medio de pago');

    $baseUrl = rtrim((string) config('app.url'), '/');

    $normalizarAsset = static function ($valor) use ($baseUrl) {
        if (empty($valor)) {
            return null;
        }

        $valor = (string) $valor;

        if (preg_match('/^https?:\/\//i', $valor) || str_starts_with($valor, 'data:image/')) {
            return $valor;
        }

        if (str_starts_with($valor, '/storage/')) {
            return $baseUrl . $valor;
        }

        if (str_starts_with($valor, 'storage/')) {
            return $baseUrl . '/' . ltrim($valor, '/');
        }

        return $baseUrl . '/storage/' . ltrim($valor, '/');
    };

    $imagenMetodoPagoRuta = (string) data_get($membresia, 'botonPago.metodoPago.imagen.ruta', '');
    $imagenMetodoPago = $normalizarAsset($imagenMetodoPagoRuta);

    if (!empty($imagenMetodoPagoRuta) && !preg_match('/^https?:\/\//i', $imagenMetodoPagoRuta)) {
        $rutaLocalImagenMetodoPago = storage_path('app/public/' . ltrim($imagenMetodoPagoRuta, '/'));

        if (is_file($rutaLocalImagenMetodoPago)) {
            if (isset($message) && is_object($message)) {
                try {
                    $imagenMetodoPago = $message->embed($rutaLocalImagenMetodoPago);
                } catch (\Throwable $e) {
                    // Si falla embed, mantener la URL normalizada.
                }
            } else {
                $contenido = @file_get_contents($rutaLocalImagenMetodoPago);
                if ($contenido !== false) {
                    $ext = strtolower(pathinfo($rutaLocalImagenMetodoPago, PATHINFO_EXTENSION));
                    $mime = match ($ext) {
                        'jpg', 'jpeg' => 'image/jpeg',
                        'gif' => 'image/gif',
                        'webp' => 'image/webp',
                        'svg' => 'image/svg+xml',
                        default => 'image/png',
                    };
                    $imagenMetodoPago = 'data:' . $mime . ';base64,' . base64_encode($contenido);
                }
            }
        }
    }

    $entidadPrincipalMail = $entidadPrincipal ?? null;
    if (!$entidadPrincipalMail && class_exists(\App\Models\Entidad::class)) {
        $entidadPrincipalMail = \App\Models\Entidad::query()
            ->where('entidad_principal', true)
            ->first();
    }

    $logoEntidadPrincipal = $normalizarAsset(data_get($entidadPrincipalMail, 'logo_url'))
        ?: $normalizarAsset(data_get($entidadPrincipalMail, 'logo_uri'));

    $logoFallbackPath = resource_path('images/lotus-art-logo.png');
    $logoFallbackDataUri = null;

    if (!$logoEntidadPrincipal && is_file($logoFallbackPath)) {
        $contenido = @file_get_contents($logoFallbackPath);
        if ($contenido !== false) {
            $logoFallbackDataUri = 'data:image/png;base64,' . base64_encode($contenido);
        }
    }
@endphp

    <div class="container">
        <div class="header">
            <h1>¡Inscripción Registrada!</h1>
            <p>Tarjeta Kadampa</p>
        </div>

        <div class="content">
            <div class="greeting">¡Hola {{ $nombreUsuario }}!</div>

            <p class="paragraph">
                Muchas gracias por inscribirte a la tarjeta Kadampa! ☺️💞
            </p>

            <div class="price-box">
                <div class="price-line">
                    Te contamos que el valor de la <strong>{{ $membresiaDescripcion }}</strong> es de
                    <strong>${{ number_format($membresiaValorNumero, 2, ',', '.') }}</strong>
                </div>
                <div class="price-line">
                    Tu importe a abonar es:
                    <strong>${{ number_format($membresiaValorNumero, 2, ',', '.') }}</strong>
                </div>
            </div>

            <div class="notice">
                🔹 Las membresías se abonan por mes calendario el 1 de cada mes con suscripción mensual en Mercado Pago
                (con tarjeta de crédito, débito o efectivo en cuenta según tu elección).<br><br>
                Las suscripciones pueden darse de baja en cualquier momento de manera muy sencilla.<br><br>
                Si no tenés Mercado Pago, respondé el mail de inscripción que te mandaremos al completar el formulario
                pidiendo más opciones de pago.
            </div>

            @if(!empty($botonPagoLink))
                <div class="payment-wrap">
                    <a href="{{ $botonPagoLink }}" target="_blank" rel="noopener noreferrer" class="payment-link">
                        @if(!empty($imagenMetodoPago))
                            <img src="{{ $imagenMetodoPago }}" alt="{{ $metodoPagoNombre }}">
                        @else
                            <img
                                src="https://img.shields.io/badge/Mercado%20Pago-Link-009ee3?style=for-the-badge&logo=mercadopago&logoColor=white"
                                alt="{{ $metodoPagoNombre }}"
                            >
                        @endif
                    </a>
                </div>
            @endif

            <div class="big-message">
                ¡ESTAMOS MUY FELICES QUE TE HAYAS SUMADO A LAS ACTIVIDADES DE NUESTRO CENTRO DE MEDITACIÓN KADAMPA ARGENTINA!
            </div>

            <p class="closing">
                Esperamos que lo disfrutes, te envío un cálido saludo.
            </p>

            @if(!empty($logoEntidadPrincipal) || !empty($logoFallbackDataUri))
                <div class="logo-wrap">
                    <img src="{{ $logoEntidadPrincipal ?: $logoFallbackDataUri }}" alt="Logo entidad principal">
                </div>
            @endif
        </div>

        <div class="footer">
            Milarepa - Sistema de Inscripciones<br>
            Este es un correo automático.
        </div>
    </div>
</body>
</html>
