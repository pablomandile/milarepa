<?php

namespace App\Http\Controllers;

use App\Http\Requests\LinkGrabacionRequest;
use Illuminate\Http\Request;
use App\Models\Grabacion;
use App\Models\LinkGrabacion;
use App\Http\Requests\GrabacionRequest;
use Inertia\Inertia;


class GrabacionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $grabaciones = Grabacion::with('linksgrabacion')->get();
       
        return inertia('Grabaciones/Index', [
            'grabaciones' => $grabaciones->toArray()
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Grabaciones/CreateFirstStep');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GrabacionRequest $request)
    {
        $grabacion = Grabacion::create($request->validated());

        return redirect()
        ->route('grabaciones.edit', $grabacion->id)
        ->with('success', 'Grabacion creada con éxito. Ahora agrega Links.');
    }

    public function storeLink(LinkGrabacionRequest $request, $id)
    {
        $grabacion = Grabacion::findOrFail($id);

       
        // Crea la fila en la tabla intermedia (esquema_precio_membresias)
        $grabacion->linksgrabacion()->create($request->validated());

        return redirect()->route('grabaciones.edit', $id)
                        ->with('success', 'Se agregó el link a la grabación.');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // 
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $grabacion = Grabacion::with(['linksgrabacion'])->findOrFail($id);
    
        // $links = LinkGrabacion::all();


        return Inertia::render('Grabaciones/EditSecondStep', [
            'grabacion' => $grabacion,
            // 'links' => $links

        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    public function updateLink(LinkGrabacionRequest $request, $linkId)
    {
       $line = LinkGrabacion::findOrFail($linkId);

       $line->update($request->validated());

       return back()->with('success', 'Link actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grabacion $grabacion)
    {
        try {
            $grabacion->delete();
            return redirect()->route('grabaciones.index')->with('sucsess', 'La Grabación se ha eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('grabaciones.index')->with('error', 'Error al eliminar la Grabacion: '. $e->getMessage());
        }
    }

    public function destroyLink($linkId)
    {
        $line = LinkGrabacion::findOrFail($linkId);
        $line->delete();

        return back()->with('success', 'Link  eliminado.');
    }

}
