<?php

namespace App\Concerns;

use App\Models\Imagen;
use App\Services\OptimizadorImagenService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait ProcesaImagenAlGuardar
{
    /**
     * Ejecuta $persist dentro de una transacción. Si llega $file, lo procesa,
     * crea el registro Imagen y pasa su id a $persist($imagenId). Si algo falla,
     * revierte la transacción y borra el archivo físico recién escrito.
     *
     * @param  \Closure(int|null):mixed  $persist
     * @return mixed lo que devuelva $persist
     */
    protected function guardarConImagen(?UploadedFile $file, string $carpeta, OptimizadorImagenService $opt, \Closure $persist)
    {
        $path = null;

        try {
            return DB::transaction(function () use ($file, $carpeta, $opt, $persist, &$path) {
                $imagenId = null;

                if ($file) {
                    $path = $opt->procesar($file, $carpeta);
                    $imagenId = Imagen::create([
                        'nombre' => $file->getClientOriginalName(),
                        'ruta'   => $path,
                    ])->id;
                }

                return $persist($imagenId);
            });
        } catch (\Throwable $e) {
            // La transacción ya revirtió el registro Imagen; falta limpiar el archivo físico.
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }
    }
}
