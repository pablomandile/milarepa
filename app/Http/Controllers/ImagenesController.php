<?php

namespace App\Http\Controllers;

use App\Models\Entidad;
use Inertia\Inertia;
use App\Http\Requests\EntidadRequest;
use App\Models\Imagen;
use App\Services\OptimizadorImagenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


class ImagenesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $imagenes = Imagen::orderByDesc('created_at')->orderByDesc('id')->paginate(36);
        return inertia('Imagenes/Index', [
            'imagenes' => $imagenes->items(),
            'links' => $imagenes->toArray()['links']
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param \Illuminate\Http\Response
     */
    public function store(Request $request, OptimizadorImagenService $optimizador)
    {
        $request->validate(
            [
                'imagen' => 'required|image|max:4096',
            ],
            [
                'imagen.max' => 'La imagen supera el tamaño máximo permitido (4 MB).',
                'imagen.image' => 'El archivo debe ser una imagen válida.',
                'imagen.required' => 'Debes seleccionar una imagen.',
            ]
        );

        $path = $optimizador->procesar($request->file('imagen'), 'img/actividades');

        Imagen::create([
            'nombre' => $request->file('imagen')->getClientOriginalName(),
            'ruta'   => $path,
        ]);

        return redirect()->route('imagenes.index')
            ->with('success', 'Imagen subida correctamente');
    }

    public function storeJson(Request $request, OptimizadorImagenService $optimizador)
    {
        $request->validate(
            [
                'imagen' => 'required|image|max:4096',
            ],
            [
                'imagen.max' => 'La imagen supera el tamaño máximo permitido (4 MB).',
                'imagen.image' => 'El archivo debe ser una imagen válida.',
                'imagen.required' => 'Debes seleccionar una imagen.',
            ]
        );

        $folder = (string) $request->input('folder', 'img/actividades');
        $allowedFolders = ['img/actividades', 'img/clases', 'img/maestros', 'img/mpago', 'img/puyas', 'img/membresias', 'img/libros'];
        if (!in_array($folder, $allowedFolders, true)) {
            $folder = 'img/actividades';
        }

        $path = $optimizador->procesar($request->file('imagen'), $folder);

        $img = Imagen::create([
            'nombre' => $request->file('imagen')->getClientOriginalName(),
            'ruta'   => $path,
        ]);

        return response()->json([
            'id' => $img->id,
            'path' => '/storage/' . $path,
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $imagen = Imagen::findOrFail($id);

        // La imagen puede estar referenciada por varias entidades. La FK de `actividades`
        // es ON DELETE CASCADE (borrar la imagen borraría la actividad, y puede fallar si
        // ésta tiene inscripciones); las demás son SET NULL. Para no perder datos ni fallar
        // en silencio, si la imagen está en uso NO la borramos y avisamos con detalle.
        $usos = $this->usosDeImagen($imagen->id);
        if ($usos->isNotEmpty()) {
            $detalle = $usos->map(fn ($u) => "{$u['cantidad']} {$u['etiqueta']}")->implode(', ');

            return back()->withErrors([
                'imagen' => "No se puede eliminar: la imagen está en uso por {$detalle}. Desvinculála de esas entidades antes de borrarla.",
            ]);
        }

        try {
            $ruta = $imagen->ruta;
            $imagen->delete();
            if ($ruta) {
                Storage::disk('public')->delete($ruta);
            }

            return redirect()->route('imagenes.index')->with('success', 'Imagen eliminada con éxito.');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'imagen' => 'Error al eliminar la imagen: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Cuenta en qué entidades está en uso una imagen (por su FK imagen_id / comprobante_id).
     * Devuelve solo las que tienen al menos una referencia, con una etiqueta legible.
     */
    private function usosDeImagen(int $imagenId)
    {
        $referencias = [
            ['tabla' => 'actividades', 'columna' => 'imagen_id', 'etiqueta' => 'actividad(es)'],
            ['tabla' => 'clases', 'columna' => 'imagen_id', 'etiqueta' => 'clase(s)'],
            ['tabla' => 'libros', 'columna' => 'imagen_id', 'etiqueta' => 'libro(s)'],
            ['tabla' => 'maestros', 'columna' => 'imagen_id', 'etiqueta' => 'maestro(s)'],
            ['tabla' => 'membresias', 'columna' => 'imagen_id', 'etiqueta' => 'membresía(s)'],
            ['tabla' => 'metodos_pago', 'columna' => 'imagen_id', 'etiqueta' => 'método(s) de pago'],
            ['tabla' => 'paginas_actividades_online', 'columna' => 'imagen_id', 'etiqueta' => 'página(s) online'],
            ['tabla' => 'ventas', 'columna' => 'comprobante_id', 'etiqueta' => 'venta(s)'],
            // Comprobantes de pago (evita borrar una imagen en uso como comprobante).
            ['tabla' => 'cobro_comprobantes', 'columna' => 'imagen_id', 'etiqueta' => 'comprobante(s) de cobro'],
            ['tabla' => 'inscripcion_comprobantes', 'columna' => 'imagen_id', 'etiqueta' => 'comprobante(s) de inscripción'],
            ['tabla' => 'estado_cuenta_membresias', 'columna' => 'comprobante_imagen_id', 'etiqueta' => 'comprobante(s) de membresía'],
        ];

        return collect($referencias)
            ->map(function ($ref) use ($imagenId) {
                return [
                    'etiqueta' => $ref['etiqueta'],
                    'cantidad' => DB::table($ref['tabla'])->where($ref['columna'], $imagenId)->count(),
                ];
            })
            ->filter(fn ($uso) => $uso['cantidad'] > 0)
            ->values();
    }
}
