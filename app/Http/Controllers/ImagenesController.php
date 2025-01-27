<?php

namespace App\Http\Controllers;

use App\Models\Entidad;
use Inertia\Inertia;
use App\Http\Requests\EntidadRequest;
use App\Models\Imagen;
use Illuminate\Http\Request;

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
            // si quieres paginación, puedes también mandar enlaces, etc.
            'links' => $imagenes->links()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validar el archivo (puede ser required|image|mimes:jpeg,png ...)
        $request->validate([
            'imagen' => 'required|image|max:2048'
        ]);

        // Almacenar la imagen en /storage/app/public/img/actividades
        // Retorna la ruta del archivo (por ejemplo "img/actividades/xyz.jpg")
        $path = $request->file('imagen')->store('img/actividades', 'public');

        // Guardar en la BD
        // Ejemplo: "nombre" = nombre real, "ruta" = "/storage/img/actividades/xyz.jpg"
        $imagen = new Imagen();
        $imagen->nombre = $request->file('imagen')->getClientOriginalName();
        $imagen->ruta   = '/storage/' . $path; 
        $imagen->save();

        // Retornar a la lista de imágenes
        return redirect()->route('imagenes.index')
            ->with('success', 'Imagen subida correctamente');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $imagen = Imagen::findorfail($id);
            $imagen->delete();
            return redirect()->route('imagenes.index')->with('success', 'Imagen eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('imagenes.index')->with('error', 'Error al eliminar la Imagen: ' . $e->getMessage());
        }

    }
}