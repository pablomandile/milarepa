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
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>¡Inscripción Confirmada!</h1>
            <p>Tu registro ha sido procesado exitosamente</p>
            <div class="success-badge">✓ Confirmado</div>
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

                @if($actividad->modalidad)
                <div class="activity-detail">
                    <div class="label">Modalidad:</div>
                    <div class="value">
                        <span class="badge">{{ $actividad->modalidad->nombre }}</span>
                    </div>
                </div>
                @endif

                @if($actividad->descripcion)
                <div class="activity-detail">
                    <div class="label">Descripción:</div>
                    <div class="value" style="color: #666; font-size: 13px;">
                        {{ substr($actividad->descripcion->descripcion, 0, 150) }}{{ strlen($actividad->descripcion->descripcion) > 150 ? '...' : '' }}
                    </div>
                </div>
                @endif
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
                        {{ $inscripcion->estado->nombre ?? 'Confirmada' }}
                    </span>
                </div>

                @if($inscripcion->precioGeneral)
                <div class="price-box">
                    <div class="price-label">Precio General</div>
                    <div class="price-value">${{ number_format($inscripcion->precioGeneral, 2, ',', '.') }}</div>
                </div>
                @endif

                @if($inscripcion->montoapagar)
                <div class="price-box">
                    <div class="price-label">Monto a Pagar</div>
                    <div class="price-value">${{ number_format($inscripcion->montoapagar, 2, ',', '.') }}</div>
                </div>
                @endif

                <div class="info-row">
                    <strong>Estado de Pago:</strong> 
                    <span class="badge" style="background-color: 
                        {{ $inscripcion->pago === 'total' ? '#28a745' : ($inscripcion->pago === 'parcial' ? '#ffc107' : '#dc3545') }};">
                        {{ ucfirst($inscripcion->pago) }}
                    </span>
                </div>

                @if($inscripcion->hospedaje)
                <div class="info-row">
                    <strong>Hospedaje:</strong> {{ $inscripcion->hospedaje->nombre ?? 'Incluido' }}
                </div>
                @endif

                @if($inscripcion->comida)
                <div class="info-row">
                    <strong>Comida:</strong> {{ $inscripcion->comida->nombre ?? 'Incluida' }}
                </div>
                @endif

                @if($inscripcion->transporte)
                <div class="info-row">
                    <strong>Transporte:</strong> {{ $inscripcion->transporte->nombre ?? 'Incluido' }}
                </div>
                @endif
            </div>

            <!-- Important Note -->
            <div class="important-note">
                <strong>⚠️ Importante:</strong> Conserva este email como comprobante de tu inscripción. Te recomendamos descargarlo o imprimirlo. Recibirás información adicional próximamente.
            </div>

            <!-- CTA Button -->
            <center>
                <a href="http://localhost" class="cta-button">Ver Detalles de mi Inscripción</a>
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
            <p><strong>Milarepa - Sistema de Inscripciones</strong></p>
            <p>Este es un correo automático. Por favor, no respondas a este mensaje.</p>
            <p>&copy; {{ now()->year }} Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
