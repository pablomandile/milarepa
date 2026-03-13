<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
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
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .success-badge {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 6px 16px;
            margin-top: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            margin-bottom: 25px;
        }
        .greeting h2 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .greeting p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .activity-card {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .activity-card h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .activity-image {
            width: 100%;
            max-height: 260px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-bottom: 15px;
        }
        .activity-detail {
            margin: 12px 0;
            display: flex;
            align-items: flex-start;
        }
        .activity-detail .label {
            font-weight: 600;
            color: #667eea;
            min-width: 120px;
            font-size: 13px;
        }
        .activity-detail .value {
            color: #555;
            font-size: 14px;
            flex: 1;
        }
        .info-section {
            background-color: #f0f4ff;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-section h4 {
            color: #667eea;
            font-size: 14px;
            margin-bottom: 12px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .info-row {
            margin: 10px 0;
            font-size: 14px;
            color: #555;
        }
        .info-row strong {
            color: #333;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            margin: 25px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .divider {
            border: none;
            border-top: 1px solid #e0e0e0;
            margin: 25px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
        .footer p {
            margin: 8px 0;
        }
        .important-note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
            color: #856404;
        }
        .important-note strong {
            color: #333;
        }
        .price-box {
            background-color: #f0f4ff;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            margin: 15px 0;
        }
        .price-label {
            color: #667eea;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .price-value {
            color: #333;
            font-size: 24px;
            font-weight: 700;
        }
        .badge {
            display: inline-block;
            background-color: #667eea;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 5px 5px 5px 0;
        }
        @media only screen and (max-width: 600px) {
            .container {
                border-radius: 0;
            }
            .header {
                padding: 28px 16px;
            }
            .header h1 {
                font-size: 20px;
            }
            .content {
                padding: 20px 16px;
            }
            .activity-card {
                padding: 16px;
            }
            .activity-detail {
                display: block;
                margin: 10px 0;
            }
            .activity-detail .label {
                display: block;
                min-width: 0;
                margin-bottom: 4px;
            }
            .activity-detail .value {
                display: block;
            }
            .info-row strong {
                display: block;
                margin-bottom: 2px;
            }
            .activity-image {
                max-height: 200px;
            }
            .price-value {
                font-size: 20px;
            }
            .cta-button {
                display: block;
                width: 100%;
                text-align: center;
                padding: 12px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>¡Inscripción Registrada!</h1>
            <p>Tu registro a la actividad ha sido procesado exitosamente</p>
            <div class="success-badge">✔ Registrado</div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Greeting -->
            <div class="greeting">
                <h2>Hola {{ $usuario->name }},</h2>
                <p>
                    Nos complace confirmar que tu inscripción a la siguiente actividad ha sido registrada correctamente en nuestro sistema.
                </p>
            </div>

            <!-- Activity Card -->
            <div class="activity-card">
                <h3>{{ $actividad->nombre }}</h3>
                @php
                    $rutaImagen = data_get($actividad, 'imagen.ruta');
                    $rutaFallback = 'img/actividades/imagen-no-disponible.jpg';
                    $baseUrl = rtrim((string) config('app.url'), '/');
                    $forzarImagenPrueba = isset($esPreviewPrueba) && $esPreviewPrueba === true;

                    if ($forzarImagenPrueba) {
                        $imagenUrl = '/storage/' . $rutaFallback;
                    } elseif ($rutaImagen && preg_match('/^https?:\/\//i', $rutaImagen)) {
                        $imagenUrl = $rutaImagen;
                    } elseif ($rutaImagen) {
                        $imagenUrl = $baseUrl . '/storage/' . ltrim($rutaImagen, '/');
                    } else {
                        $imagenUrl = $baseUrl . '/storage/' . $rutaFallback;
                    }

                    $rutaLocal = $rutaImagen
                        ? storage_path('app/public/' . ltrim($rutaImagen, '/'))
                        : storage_path('app/public/' . $rutaFallback);

                    if (!$forzarImagenPrueba && isset($message) && is_object($message) && file_exists($rutaLocal)) {
                        try {
                            $imagenUrl = $message->embed($rutaLocal);
                        } catch (\Throwable $e) {
                            // Mantener URL externa si falla el embed.
                        }
                    }
                @endphp
                <img
                    class="activity-image"
                    src="{{ $imagenUrl }}"
                    alt="Imagen de la actividad"
                />
                
                <div class="activity-detail">
                    <div class="label">Fecha y Hora:</div>
                    <div class="value">
                        {{ $actividad->fecha_inicio ? \Carbon\Carbon::parse($actividad->fecha_inicio)->translatedFormat('j \d\e F \d\e Y \a \l\a\s H:i') . ' hs.' : 'Por confirmar' }}
                    </div>
                </div>

                @if($actividad->entidad)
                <div class="activity-detail">
                    <div class="label">Organizador:</div>
                    <div class="value">{{ $actividad->entidad->nombre }}</div>
                </div>
                @endif

                <div class="activity-detail">
                    <div class="label">Modalidad:</div>
                    <div class="value">
                        @php
                            $inscripcionOnline = data_get($inscripcion, 'online', false);
                        @endphp
                        <span class="badge">{{ $inscripcionOnline ? 'Online' : 'Presencial' }}</span>
                    </div>
                </div>


            </div>

            <!-- Inscription Details -->
            <div class="info-section">
                <h4>Detalles de tu Inscripción</h4>
                
                <div class="info-row">
                    <strong>Número de Inscripción:</strong> #{{ $inscripcion->id }}
                </div>

                <div class="info-row">
                    <strong>Membresía:</strong> {{ $inscripcion->membresia }}
                </div>

                <div class="info-row">
                    <strong>Estado:</strong> 
                    <span class="badge" style="background-color: #28a745; margin-left: 5px;">
                        {{ data_get($inscripcion, 'estado', 'Registrada') }}
                    </span>
                </div>

                @if($inscripcion->precioGeneral)
                <div class="price-box">
                    <div class="price-label">Precio General Actividad</div>
                    <div class="price-value">${{ number_format($inscripcion->precioGeneral, 2, ',', '.') }}</div>
                </div>
                @endif

                @if($inscripcion->montoapagar)
                <div class="price-box">
                    <div class="price-label">Monto a Pagar Total</div>
                    <div class="price-value">${{ number_format($inscripcion->montoapagar, 2, ',', '.') }}</div>
                </div>
                @endif

                @php
                    $tieneEfectivoTarjetas = false;
                    $metodoTransferencia = null;
                    $montoActividad = (float) data_get($inscripcion, 'montoActividad', 0);
                    $montoGrabacion = data_get($inscripcion, 'montoGrabacion');
                    $montoHospedaje = data_get($inscripcion, 'montoHospedaje');
                    $montoComidas = data_get($inscripcion, 'montoComidas');
                    $montoTransporte = data_get($inscripcion, 'montoTransporte');
                    $actividadGetnetLink = data_get($actividad, 'botonPago.link');

                    if (is_object($actividad) && method_exists($actividad, 'loadMissing')) {
                        $actividad->loadMissing([
                            'metodosPago',
                            'botonPago',
                            'esquemaPrecio.membresias.membresia',
                            'esquemaPrecio.membresias.botonPago',
                            'esquemaDescuento.membresias.membresia',
                            'esquemaDescuento.membresias.botonPago',
                            'grabacion.botonPago',
                        ]);
                    }

                    if (is_object($inscripcion) && method_exists($inscripcion, 'loadMissing')) {
                        $inscripcion->loadMissing([
                            'hospedaje.botonPago',
                            'comida.botonPago',
                            'transporte.botonPago',
                            'user.membresia',
                        ]);
                    }

                    $metodosPago = collect($actividad->metodosPago ?? []);
                    $normalizar = function ($texto) {
                        $valor = mb_strtolower(trim((string) $texto), 'UTF-8');
                        return strtr($valor, [
                            'á' => 'a',
                            'é' => 'e',
                            'í' => 'i',
                            'ó' => 'o',
                            'ú' => 'u',
                        ]);
                    };

                    $metodosNormalizados = $metodosPago->map(function ($metodo) use ($normalizar) {
                        return $normalizar(data_get($metodo, 'nombre'));
                    });

                    $tieneEfectivoTarjetas = $metodosNormalizados->contains(fn ($nombre) =>
                        in_array($nombre, ['efectivo', 'tarjeta de credito', 'tarjeta de debito'], true)
                    );
                    $metodoTransferencia = $metodosPago->first(function ($metodo) use ($normalizar) {
                        return $normalizar(data_get($metodo, 'nombre')) === 'transferencia';
                    });

                    if (empty($actividadGetnetLink)) {
                        $lineasEsquema = collect();
                        if (data_get($actividad, 'esquemaDescuento.membresias')) {
                            $lineasEsquema = $lineasEsquema->concat($actividad->esquemaDescuento->membresias);
                        }
                        if (data_get($actividad, 'esquemaPrecio.membresias')) {
                            $lineasEsquema = $lineasEsquema->concat($actividad->esquemaPrecio->membresias);
                        }

                        $membresiaUserId = data_get($inscripcion, 'user.membresia_id');
                        $lineaBotonActividad = $lineasEsquema->first(function ($linea) use ($membresiaUserId, $montoActividad) {
                            return (int) data_get($linea, 'membresia_id') === (int) $membresiaUserId
                                && abs(((float) data_get($linea, 'precio', 0)) - $montoActividad) < 0.01
                                && !empty(data_get($linea, 'botonPago.link'));
                        });

                        if (!$lineaBotonActividad) {
                            $lineaBotonActividad = $lineasEsquema->first(function ($linea) use ($montoActividad) {
                                return abs(((float) data_get($linea, 'precio', 0)) - $montoActividad) < 0.01
                                    && !empty(data_get($linea, 'botonPago.link'));
                            });
                        }

                        if ($lineaBotonActividad) {
                            $actividadGetnetLink = data_get($lineaBotonActividad, 'botonPago.link');
                        }
                    }
                @endphp

                @if(($tieneEfectivoTarjetas ?? false) || ($metodoTransferencia ?? null))
                <div class="info-row" style="margin-top: 14px;">
                    <strong>Métodos de pago:</strong>
                </div>
                @endif

                @if($tieneEfectivoTarjetas ?? false)
                <div class="info-row">
                    Podes pagar en efectivo, tarjeta de crédito y tarjeta de débito en el lugar antes de comenzar. Tu inscripción quedará en estado pendiente para aprobación.
                </div>
                @endif

                @if($metodoTransferencia ?? null)
                <div class="info-row">
                    <strong>Transferencia:</strong>
                    {{ data_get($metodoTransferencia, 'descripcion') ?: 'Subí un comprobante (PDF o imagen) para registrar el pago.' }}
                </div>
                @endif

                <div class="info-row" style="margin-top: 12px;">
                    <strong>Actividad:</strong>
                    ${{ number_format((float) ($montoActividad ?? 0), 2, ',', '.') }}
                    @if($actividadGetnetLink ?? null)
                        <a href="{{ $actividadGetnetLink }}" target="_blank" style="margin-left:8px;display:inline-block;background:#4f46e5;color:#fff;text-decoration:none;padding:4px 10px;border-radius:4px;font-size:12px;">Pagar con Getnet</a>
                    @endif
                </div>

                @if(!is_null($montoGrabacion ?? null))
                <div class="info-row">
                    <strong>Grabación:</strong>
                    ${{ number_format((float) ($montoGrabacion ?? 0), 2, ',', '.') }}
                    @if(data_get($actividad, 'grabacion.botonPago.link'))
                        <a href="{{ data_get($actividad, 'grabacion.botonPago.link') }}" target="_blank" style="margin-left:8px;display:inline-block;background:#4f46e5;color:#fff;text-decoration:none;padding:4px 10px;border-radius:4px;font-size:12px;">Pagar con Getnet</a>
                    @endif
                </div>
                @endif

                @if($inscripcion->hospedaje || !is_null($montoHospedaje ?? null))
                <div class="info-row">
                    <strong>Hospedaje:</strong>
                    @if($inscripcion->hospedaje)
                        {{ $inscripcion->hospedaje->nombre ?? 'Incluido' }}
                    @endif
                    @if(!is_null($montoHospedaje ?? null))
                        ( ${{ number_format((float) ($montoHospedaje ?? 0), 2, ',', '.') }} )
                    @endif
                    @if(data_get($inscripcion, 'hospedaje.botonPago.link'))
                        <a href="{{ data_get($inscripcion, 'hospedaje.botonPago.link') }}" target="_blank" style="margin-left:8px;display:inline-block;background:#4f46e5;color:#fff;text-decoration:none;padding:4px 10px;border-radius:4px;font-size:12px;">Pagar con Getnet</a>
                    @endif
                </div>
                @endif

                @if($inscripcion->comida || !is_null($montoComidas ?? null))
                <div class="info-row">
                    <strong>Comida:</strong>
                    @if($inscripcion->comida)
                        {{ $inscripcion->comida->nombre ?? 'Incluida' }}
                    @endif
                    @if(!is_null($montoComidas ?? null))
                        ( ${{ number_format((float) ($montoComidas ?? 0), 2, ',', '.') }} )
                    @endif
                    @if(data_get($inscripcion, 'comida.botonPago.link'))
                        <a href="{{ data_get($inscripcion, 'comida.botonPago.link') }}" target="_blank" style="margin-left:8px;display:inline-block;background:#4f46e5;color:#fff;text-decoration:none;padding:4px 10px;border-radius:4px;font-size:12px;">Pagar con Getnet</a>
                    @endif
                </div>
                @endif

                @if($inscripcion->transporte || !is_null($montoTransporte ?? null))
                <div class="info-row">
                    <strong>Transporte:</strong>
                    @if($inscripcion->transporte)
                        {{ $inscripcion->transporte->nombre ?? $inscripcion->transporte->descripcion ?? 'Incluido' }}
                    @endif
                    @if(!is_null($montoTransporte ?? null))
                        ( ${{ number_format((float) ($montoTransporte ?? 0), 2, ',', '.') }} )
                    @endif
                    @if(data_get($inscripcion, 'transporte.botonPago.link'))
                        <a href="{{ data_get($inscripcion, 'transporte.botonPago.link') }}" target="_blank" style="margin-left:8px;display:inline-block;background:#4f46e5;color:#fff;text-decoration:none;padding:4px 10px;border-radius:4px;font-size:12px;">Pagar con Getnet</a>
                    @endif
                </div>
                @endif

                <div class="info-row">
                    <strong>Estado de Pago:</strong> 
                    <span class="badge" style="background-color: 
                        {{ $inscripcion->pago === 'Saldado' ? '#28a745' : ($inscripcion->pago === 'Parcial' ? '#ffc107' : '#dc3545') }};">
                        {{ ucfirst($inscripcion->pago) }}
                    </span>
                </div>

            </div>

            <!-- Important Note -->
            <div class="important-note">
                <strong>⚠️ Importante:</strong> Conserva este email como comprobante del registro de tu inscripción. Recibirás información adicional próximamente.
            </div>

            <!-- CTA Button -->
            <center>
                <a href="{{ url('/welcome') }}" class="cta-button">Ver Detalles de mi Inscripción</a>
            </center>

            <hr class="divider">

            <!-- Additional Information -->
            <div style="font-size: 14px; color: #666; margin-bottom: 20px;">
                <p>
                    <strong>¿Necesitas más información?</strong><br>
                    Si tienes preguntas sobre tu inscripción, por favor dirígete a nuestro Centro de Ayuda o contáctanos directamente.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            @include('emails.partials.logo_entidad_principal')
            <p><strong>Milarepa - Sistema de Inscripciones</strong></p>
            <p>Este es un correo automático. Por favor, no respondas a este mensaje.</p>
            <p>&copy; {{ now()->year }} Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
