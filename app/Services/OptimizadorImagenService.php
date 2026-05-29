<?php

namespace App\Services;

use App\Models\ConfiguracionSistema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OptimizadorImagenService
{
    private const CLAVE_OPTIMIZAR = 'optimizar_imagenes_webp';
    private const CALIDAD_WEBP = 85;
    private const MIMES_IMAGEN = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public function procesar(UploadedFile $file, string $carpeta, string $disco = 'public'): string
    {
        if (!ConfiguracionSistema::obtenerBoolean(self::CLAVE_OPTIMIZAR, false)) {
            return $file->store($carpeta, $disco);
        }

        $mime = $file->getMimeType();
        if (!in_array($mime, self::MIMES_IMAGEN, true)) {
            return $file->store($carpeta, $disco);
        }

        $rutaWebp = $this->convertirAWebp($file, $carpeta, $disco, $mime);

        if ($rutaWebp !== null) {
            return $rutaWebp;
        }

        Log::warning('OptimizadorImagenService: fallback al guardar sin conversión.', [
            'archivo' => $file->getClientOriginalName(),
            'mime' => $mime,
        ]);

        return $file->store($carpeta, $disco);
    }

    private function convertirAWebp(UploadedFile $file, string $carpeta, string $disco, string $mime): ?string
    {
        if (!function_exists('imagewebp')) {
            return null;
        }

        $imagen = $this->cargarImagen($file->getPathname(), $mime);
        if ($imagen === null) {
            return null;
        }

        $nombreUnico = Str::random(40) . '.webp';
        $rutaRelativa = trim($carpeta, '/') . '/' . $nombreUnico;
        $pathAbsoluto = Storage::disk($disco)->path($rutaRelativa);

        $directorio = dirname($pathAbsoluto);
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            imagedestroy($imagen);
            return null;
        }

        $exito = @imagewebp($imagen, $pathAbsoluto, self::CALIDAD_WEBP);
        imagedestroy($imagen);

        if (!$exito) {
            if (file_exists($pathAbsoluto)) {
                @unlink($pathAbsoluto);
            }
            return null;
        }

        return $rutaRelativa;
    }

    /**
     * @return \GdImage|null
     */
    private function cargarImagen(string $path, string $mime)
    {
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => $this->cargarPng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => null,
        };

        return $img ?: null;
    }

    /**
     * @return \GdImage|false
     */
    private function cargarPng(string $path)
    {
        $img = @imagecreatefrompng($path);
        if (!$img) {
            return false;
        }

        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        return $img;
    }
}
