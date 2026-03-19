<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; color: #333; margin: 0; padding: 0; }
        .container { max-width: 920px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: #fff; padding: 28px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 8px 0 0 0; opacity: .95; font-size: 14px; }
        .content { padding: 24px; }
        .cards { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; }
        .card { flex: 1 1 140px; border-radius: 10px; padding: 12px; color: #fff; min-width: 130px; }
        .card .title { font-size: 11px; opacity: .9; margin-bottom: 6px; }
        .card .value { font-size: 22px; font-weight: 700; }
        .indigo { background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); }
        .emerald { background: linear-gradient(135deg, #10b981 0%, #047857 100%); }
        .sky { background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%); }
        .violet { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); }
        .amber { background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%); }
        .rose { background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%); }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead th { background: #eef2ff; color: #1f2937; text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb; }
        tbody td { padding: 10px; border-bottom: 1px solid #f1f5f9; color: #374151; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer { padding: 20px 24px; color: #6b7280; font-size: 12px; text-align: center; background: #f8fafc; }
        .empty { padding: 20px; border: 1px dashed #cbd5e1; border-radius: 8px; color: #64748b; text-align: center; margin-top: 12px; }
        @media (max-width: 640px) {
            .content { padding: 16px; }
            .header { padding: 20px 16px; }
            table { font-size: 12px; }
            thead th, tbody td { padding: 8px; }
        }
    </style>
</head>
<body>
@php
    $resumen = $resumen ?? [];
    $actividades = $actividades ?? [];
    $rango = $rango ?? null;
@endphp

<div class="container">
    <div class="header">
        <h1>Reporte Semanal de Inscripciones por Actividad</h1>
        <p>
            @if($rango)
                Semana del {{ $rango['desde'] ?? '-' }} al {{ $rango['hasta'] ?? '-' }}
            @else
                Resumen semanal actualizado
            @endif
        </p>
    </div>

    <div class="content">
        <div class="cards">
            <div class="card indigo"><div class="title">Eventos activos</div><div class="value">{{ $resumen['eventos_activos'] ?? 0 }}</div></div>
            <div class="card emerald"><div class="title">Total de inscriptos</div><div class="value">{{ $resumen['total_inscriptos'] ?? 0 }}</div></div>
            <div class="card sky"><div class="title">Inscriptos con TK</div><div class="value">{{ $resumen['inscriptos_con_tk'] ?? 0 }}</div></div>
            <div class="card violet"><div class="title">Inscriptos no TK</div><div class="value">{{ $resumen['inscriptos_sin_tk'] ?? 0 }}</div></div>
            <div class="card amber"><div class="title">Inscriptos últimos 5 días</div><div class="value">{{ $resumen['inscriptos_ultimos_5_dias'] ?? 0 }}</div></div>
            <div class="card rose"><div class="title">Pendientes de pago</div><div class="value">{{ $resumen['pendientes_pago'] ?? 0 }}</div></div>
        </div>

        @if(!empty($actividades))
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Maestr@</th>
                        <th>Fecha</th>
                        <th class="text-center">Días restantes</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Últimos 5 días</th>
                        <th class="text-center">Pendientes</th>
                        <th class="text-right">Pendiente (importe)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($actividades as $actividad)
                        <tr>
                            <td>{{ $actividad['nombre'] ?? '-' }}</td>
                            <td>{{ $actividad['maestro'] ?? '-' }}</td>
                            <td>{{ $actividad['fecha_formateada'] ?? '-' }}</td>
                            <td class="text-center">{{ $actividad['dias_restantes'] ?? '-' }}</td>
                            <td class="text-center">{{ $actividad['total_inscriptos'] ?? 0 }}</td>
                            <td class="text-center">{{ $actividad['inscriptos_ultimos_5_dias'] ?? 0 }}</td>
                            <td class="text-center">{{ $actividad['pendientes_pago'] ?? 0 }}</td>
                            <td class="text-right">${{ number_format((float) ($actividad['pendiente_importe'] ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">No hay actividades activas con inscripciones para este período.</div>
        @endif
    </div>

    <div class="footer">
        @include('emails.partials.logo_entidad_principal')
        <p>Reporte automático del sistema de inscripciones.</p>
    </div>
</div>
</body>
</html>
