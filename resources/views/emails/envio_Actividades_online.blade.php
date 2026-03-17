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
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.95;
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
        .greeting h2 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .greeting p {
            color: #666;
            font-size: 14px;
            margin-bottom: 14px;
        }
        .info-section {
            background-color: #f0f4ff;
            border-radius: 6px;
            padding: 20px;
            margin: 22px 0;
        }
        .info-section p {
            color: #4b5563;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .cta-wrapper {
            text-align: center;
            margin: 28px 0;
        }
        .cta-label {
            font-size: 12px;
            color: #667eea;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            padding: 14px 26px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 24px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 26px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        .footer p {
            margin: 6px 0;
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
            .cta-button {
                display: block;
                width: 100%;
                text-align: center;
                padding: 12px 14px;
            }
        }
    </style>
</head>
<body>
@php
    $nombrePracticante = $nombrePracticante ?? $nombre_practicante ?? ($usuario->name ?? 'practicante');
    $mesOnline = $mesOnline ?? $mes_online ?? 'MARZO';
    $linkActividadesOnline = $linkActividadesOnline ?? $link_actividades_online ?? 'https://milarepa.com.ar/actividades-online';
@endphp

<div class="container">
    <div class="header">
        <h1>BIENVENIDA A LA COMUNIDAD KADAMPA</h1>
        <p>Enlaces clases online mes de {{ strtoupper($mesOnline) }}</p>
        <div class="success-badge">Clases en vivo</div>
    </div>

    <div class="content">
        <div class="greeting">
            <h2>Querid@ {{ $nombrePracticante }},</h2>
            <p>Esperamos te encuentres muy bien.</p>
            <p>Te damos la bienvenida a la comunidad Kadampa de Argentina.</p>
            <p>
                En este mail te enviamos el enlace a la web donde encontrarás todas las actividades online
                que ofrece el Centro Kadampa de Palermo; las mismas sólo serán transmitidas en vivo y no quedarán grabadas.
            </p>
        </div>

        <div class="info-section">
            <p><strong>DALE CLICK AL ENLACE DE ABAJO</strong></p>
        </div>

        <div class="cta-wrapper">
            <a href="{{ $linkActividadesOnline }}" class="cta-button" target="_blank" rel="noopener noreferrer">
                WEB CLASES ONLINE MES DE {{ strtoupper($mesOnline) }}
            </a>
        </div>

        <hr class="divider">
    </div>

    <div class="footer">
        @include('emails.partials.logo_entidad_principal')
        <p>Comunidad Kadampa Argentina</p>
    </div>
</div>
</body>
</html>
