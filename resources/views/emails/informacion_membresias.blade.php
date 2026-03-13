<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1f2937; }
        .container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); color: #fff; padding: 28px 24px; text-align: center; }
        .content { padding: 24px; }
        .card { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 16px; }
        .title { margin: 0 0 10px; color: #1d4ed8; font-size: 16px; font-weight: 700; }
        .row { margin: 8px 0; font-size: 14px; line-height: 1.55; }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
            color: #ffffff !important;
            padding: 12px 22px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }
        .footer { border-top: 1px solid #e5e7eb; padding: 16px 24px 22px; color: #6b7280; font-size: 12px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin:0;font-size:24px;">Información solicitada sobre Tarjetas Kadampa</h1>
            <p style="margin:8px 0 0;font-size:14px;opacity:.95;">
                Gracias por tu interés en nuestras actividades
            </p>
        </div>

        <div class="content">
            <p style="margin:0 0 16px;font-size:15px;">
                ¡Hola {{ data_get($usuario, 'name', 'amig@') }}!<br>
                Espero que estés muy bien.
            </p>

            <div class="card">
                <h2 class="title">Tarjetas Kadampa</h2>
                <p class="row">
                    Muchas gracias por tu interés en nuestras actividades.
                </p>
                <p class="row">
                    Te contamos que las Tarjetas Kadampa (TK) están diseñadas para aquellas personas que tienen el deseo de tomar clases de budismo y meditación con regularidad, contribuir con el desarrollo del "Centro de Meditación Kadampa Argentina" y al mismo tiempo beneficiarse con importantes descuentos en las actividades especiales, cursos y retiros.
                </p>
                <p class="row" style="margin-bottom: 4px;">
                    Tenemos 3 Tarjetas Kadampa:
                </p>
                <p class="row" style="margin: 4px 0 0 14px;">1) La TK Clases.</p>
                <p class="row" style="margin: 4px 0 0 14px;">2) La TK Corazón.</p>
                <p class="row" style="margin: 4px 0 0 14px;">3) La TK Benefactor.</p>
                <p class="row" style="margin-top: 12px;">
                    Ingresá al link de inscripción para conocer los precios y los beneficios de cada una de ellas.
                </p>
            </div>

            <div style="text-align:center; margin: 22px 0;">
                <a
                    href="{{ url('/membresias/public') }}"
                    class="cta-button"
                >
                    Tarjetas Kadampa
                </a>
            </div>

            <div style="font-size:14px;color:#4b5563;margin-top:8px;">
                ¡Cualquier consulta, no dejes de escribirnos!
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
