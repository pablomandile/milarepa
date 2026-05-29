<?php

namespace App\Http\Controllers;

use App\Models\Entidad;
use Inertia\Inertia;
use App\Http\Requests\EntidadRequest;
use App\Models\Imagen;
use App\Services\OptimizadorImagenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class ImagenesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $imagenes = Imagen::paginate(16);
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
        try {
            $imagen = Imagen::findorfail($id);
            $imagen->delete();
            Storage::disk('public')->delete($imagen->ruta);
            return redirect()->route('imagenes.index')->with('success', 'Imagen eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('imagenes.index')->with('error', 'Error al eliminar la Imagen: ' . $e->getMessage());
        }

    }
}
