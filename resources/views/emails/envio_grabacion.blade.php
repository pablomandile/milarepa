<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1f2937;
        }
        .container {
            max-width: 620px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
            color: #fff;
            padding: 28px 24px;
            text-align: center;
        }
        .content {
            padding: 24px;
        }
        .card {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .title {
            margin: 0 0 10px;
            color: #1d4ed8;
            font-size: 16px;
            font-weight: 700;
        }
        .row {
            margin: 8px 0;
            font-size: 14px;
            line-height: 1.45;
        }
        .row strong {
            color: #111827;
        }
        .link-row {
            margin: 10px 0;
        }
        .video-icon-btn {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            vertical-align: middle;
            margin-left: 8px;
        }
        .footer {
            border-top: 1px solid #e5e7eb;
            padding: 16px 24px 22px;
            color: #6b7280;
            font-size: 12px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin:0;font-size:24px;">Grabación Disponible</h1>
            <p style="margin:8px 0 0;font-size:14px;opacity:.95;">
                Ya puedes acceder a la grabación de tu actividad
            </p>
        </div>

        <div class="content">
            <p style="margin:0 0 16px;font-size:15px;">
                Hola {{ data_get($usuario, 'name', 'Participante') }},
            </p>

            <div class="card">
                <h2 class="title">Datos de la Actividad</h2>
                <div class="row">
                    <strong>Actividad:</strong> {{ data_get($actividad, 'nombre', '-') }}
                </div>
                <div class="row">
                    <strong>Fecha y Hora:</strong>
                    {{ data_get($actividad, 'fecha_inicio') ? \Carbon\Carbon::parse(data_get($actividad, 'fecha_inicio'))->translatedFormat('j \d\e F \d\e Y \a \l\a\s H:i') . ' hs.' : 'Por confirmar' }}
                </div>
                <div class="row">
                    <strong>Inscripción:</strong> #{{ data_get($inscripcion, 'id', '-') }}
                </div>
            </div>

            <div class="card">
                <h2 class="title">Grabación</h2>
                <div class="row">
                    <strong>Nombre:</strong> {{ data_get($actividad, 'grabacion.nombre', 'Grabación de la actividad') }}
                </div>

                @php
                    $linksGrabacion = collect(data_get($actividad, 'grabacion.linksgrabacion', []))
                        ->filter(fn ($link) => !empty(data_get($link, 'link')))
                        ->values();
                @endphp

                @if($linksGrabacion->isNotEmpty())
                    @foreach($linksGrabacion as $linkGrabacion)
                        <div class="link-row">
                            <strong>{{ data_get($linkGrabacion, 'nombre', 'Link de grabación') }}:</strong>
                            <a
                                href="{{ data_get($linkGrabacion, 'link') }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="video-icon-btn"
                                title="Abrir grabación"
                            >
                                &#9654;
                            </a>
                        </div>
                    @endforeach
                @else
                    <div class="row">No hay links de grabación disponibles por el momento.</div>
                @endif
            </div>

            <div style="text-align:center; margin: 22px 0;">
                <a
                    href="{{ url('/welcome') }}"
                    style="display:inline-block;background:linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);color:#fff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:600;font-size:14px;"
                >
                    Ver Detalles de mi Inscripción
                </a>
            </div>

            <div style="font-size:14px;color:#4b5563;margin-top:8px;">
                <p style="margin:0 0 6px;"><strong>¿Necesitas más información?</strong></p>
                <p style="margin:0;">
                    Si tienes preguntas sobre tu inscripción, por favor dirígete a nuestro Centro de Ayuda o contáctanos directamente.
                </p>
            </div>
        </div>

        <div class="footer">
            @include('emails.partials.logo_entidad_principal')
            Milarepa - Sistema de Inscripciones<br>
            Este es un correo automático.
        </div>
    </div>
</body>
</html>
