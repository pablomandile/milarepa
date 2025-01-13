<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EsquemaPrecio;
use App\Models\Membresia;
use App\Models\Moneda;
use App\Http\Requests\EsquemaPrecioRequest;
use Inertia\Inertia;
use App\Models\EsquemaPrecioMembresia;


class EsquemaPreciosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $esquemaprecios = EsquemaPrecio::with([
            'membresias.moneda',
            'membresias.membresia.entidad'  // <- Eager load de la Entidad
        ])->get();
        // dd($esquemaprecios->toArray());
        return inertia('EsquemaPrecios/Index', [
            'esquemaprecios' => $esquemaprecios->toArray()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('EsquemaPrecios/CreateFirstStep');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EsquemaPrecioRequest $request)
    {
        $esquemaPrecio = EsquemaPrecio::create($request->validated());

        return redirect()
        ->route('esquemaprecios.edit', $esquemaPrecio->id)
        ->with('success', 'Esquema de precios creado. Ahora agrega membresías.');
    }

    public function storeMembresia(Request $request, $id)
    {
        $esquema = EsquemaPrecio::findOrFail($id);

        $validated = $request->validate([
            'membresia_id' => 'required|exists:membresias,id',
            'precio' => 'required|numeric|min:0',
            'moneda_id' => 'required|exists:monedas,id'
        ]);

        // Crea la fila en la tabla intermedia (esquema_precio_membresias)
        $esquema->membresias()->create($validated);

        return redirect()->route('esquemaprecios.edit', $id)
                        ->with('success', 'Se agregó la membresía al esquema');
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
        $esquemaPrecio = EsquemaPrecio::with([
            'membresias.membresia.entidad',
            'membresias.moneda'
        ])->findOrFail($id);
    
        $membresias = Membresia::with('entidad')->get()
                       ->map(function($m){
                           $m->label = $m->nombre . ' - ' . ($m->entidad->abreviacion ?? '');
                           return $m;
                       });
        $monedas = Moneda::select('id', 'nombre', 'simbolo')->get();
    
        return Inertia::render('EsquemaPrecios/EditSecondStep', [
            'esquemaPrecio' => $esquemaPrecio,
            'membresias' => $membresias,
            'monedas' => $monedas,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    public function updateMembresia(Request $request, $membresiaId)
    {
       $line = EsquemaPrecioMembresia::findOrFail($membresiaId);

       $validated = $request->validate([
           'membresia_id' => 'required|exists:membresias,id',
           'precio' => 'required|numeric|min:0',
           'moneda_id' => 'required|exists:monedas,id',
       ]);
       logger('Antes del update', [$validated]);
        $line->update($validated);
        logger('Después del update', [$line]);
       return back()->with('success', 'Membresía actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EsquemaPrecio $esquemaprecio)
    {
        try {
            $esquemaprecio->delete();
            return redirect()->route('esquemaprecios.index')->with('sucsess', 'El esquema de precios se ha eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('esquemaprecios.index')->with('error', 'Error al eliminar el esquema de precios: '. $e->getMessage());
        }
    }

    public function destroyMembresia($membresiaId)
    {
        $line = EsquemaPrecioMembresia::findOrFail($membresiaId);
        $line->delete();

        return back()->with('success', 'Membresía eliminada.');
    }

}
