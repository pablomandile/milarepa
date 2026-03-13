@php
    $entidadPrincipalMail = $entidadPrincipal ?? null;
    if (!$entidadPrincipalMail && class_exists(\App\Models\Entidad::class)) {
        $entidadPrincipalMail = \App\Models\Entidad::query()
            ->where('entidad_principal', true)
            ->first();
    }

    $baseUrl = rtrim((string) config('app.url'), '/');
    $logoFuente = data_get($entidadPrincipalMail, 'logo_url')
        ?: data_get($entidadPrincipalMail, 'logo_uri');

    $logoEntidadPrincipal = null;
    $rutaLocalLogoEntidad = null;

    if (!empty($logoFuente)) {
        $logoFuente = (string) $logoFuente;

        if (preg_match('/^https?:\/\//i', $logoFuente) || str_starts_with($logoFuente, 'data:image/')) {
            $logoEntidadPrincipal = $logoFuente;
        } else {
            if (str_starts_with($logoFuente, '/storage/')) {
                $rutaRelativa = ltrim(substr($logoFuente, strlen('/storage/')), '/');
                $logoEntidadPrincipal = $baseUrl . $logoFuente;
            } elseif (str_starts_with($logoFuente, 'storage/')) {
                $rutaRelativa = ltrim(substr($logoFuente, strlen('storage/')), '/');
                $logoEntidadPrincipal = $baseUrl . '/' . ltrim($logoFuente, '/');
            } else {
                $rutaRelativa = ltrim($logoFuente, '/');
                $logoEntidadPrincipal = $baseUrl . '/storage/' . $rutaRelativa;
            }

            $rutaLocalLogoEntidad = storage_path('app/public/' . $rutaRelativa);
        }
    }

    if ($rutaLocalLogoEntidad && is_file($rutaLocalLogoEntidad)) {
        if (isset($message) && is_object($message)) {
            try {
                $logoEntidadPrincipal = $message->embed($rutaLocalLogoEntidad);
            } catch (\Throwable $e) {
                // Si falla el embed, se mantiene la URL normalizada.
            }
        } else {
            $contenido = @file_get_contents($rutaLocalLogoEntidad);
            if ($contenido !== false) {
                $ext = strtolower(pathinfo($rutaLocalLogoEntidad, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => 'image/png',
                };
                $logoEntidadPrincipal = 'data:' . $mime . ';base64,' . base64_encode($contenido);
            }
        }
    }

    $logoFallbackPath = resource_path('images/lotus-art-logo.png');
    $logoFallbackDataUri = null;
    if (!$logoEntidadPrincipal && is_file($logoFallbackPath)) {
        $contenidoFallback = @file_get_contents($logoFallbackPath);
        if ($contenidoFallback !== false) {
            $logoFallbackDataUri = 'data:image/png;base64,' . base64_encode($contenidoFallback);
        }
    }
@endphp

@if(!empty($logoEntidadPrincipal) || !empty($logoFallbackDataUri))
    <div style="text-align:center; margin: 8px 0 14px;">
        <img
            src="{{ $logoEntidadPrincipal ?: $logoFallbackDataUri }}"
            alt="Logo entidad principal"
            style="max-height:72px; max-width:260px; width:auto; height:auto; object-fit:contain;"
        >
    </div>
@endif

