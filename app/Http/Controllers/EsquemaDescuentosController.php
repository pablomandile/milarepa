<?php

namespace App\Http\Controllers;

use App\Models\EsquemaDescuento;
use Illuminate\Http\Request;
use App\Models\Membresia;
use App\Models\Moneda;
use App\Http\Requests\EsquemaDescuentoRequest;
use Inertia\Inertia;
use App\Models\EsquemaDescuentoMembresia;

class EsquemaDescuentosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $esquemadescuentos = EsquemaDescuento::with([
            'membresias.moneda',
            'membresias.membresia.entidad'  // <- Eager load de la Entidad
        ])->get();
        return inertia('EsquemaDescuentos/Index', [
            'esquemadescuentos' => $esquemadescuentos->toArray()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('EsquemaDescuentos/CreateFirstStep');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EsquemaDescuentoRequest $request)
    {
        $esquemaDescuento = EsquemaDescuento::create($request->validated());

        return redirect()
        ->route('esquemadescuentos.edit', $esquemaDescuento->id)
        ->with('success', 'Esquema de descuentos creado. Ahora agrega membresías.');
    }

    public function storeMembresia(Request $request, $id)
    {
        $esquema = EsquemaDescuento::findOrFail($id);

        $validated = $request->validate([
            'membresia_id' => 'required|exists:membresias,id',
            'precio' => 'required|numeric|min:0',
            'moneda_id' => 'required|exists:monedas,id'
        ]);

        // Crea la fila en la tabla intermedia (esquema_precio_membresias)
        $esquema->membresias()->create($validated);

        return redirect()->route('esquemadescuentos.edit', $id)
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
    public function edit(string $id)
    {
        $esquemaDescuento = EsquemaDescuento::with([
            'membresias.membresia.entidad',
            'membresias.moneda'
        ])->findOrFail($id);
    
        $membresias = Membresia::with('entidad')->get()
                       ->map(function($m){
                           $m->label = $m->nombre . ' - ' . ($m->entidad->abreviacion ?? '');
                           return $m;
                       });
        $monedas = Moneda::select('id', 'nombre', 'simbolo')->get();
    
        return Inertia::render('EsquemaDescuentos/EditSecondStep', [
            'esquemaDescuento' => $esquemaDescuento,
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
       $line = EsquemaDescuentoMembresia::findOrFail($membresiaId);

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
    public function destroy(EsquemaDescuento $esquemaprecio)
    {
        try {
            $esquemaprecio->delete();
            return redirect()->route('esquemadescuentos.index')->with('sucsess', 'El esquema de descuentos se ha eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('esquemadescuentos.index')->with('error', 'Error al eliminar el esquema de descuentos: '. $e->getMessage());
        }
    }

    public function destroyMembresia($membresiaId)
    {
        $line = EsquemaDescuentoMembresia::findOrFail($membresiaId);
        $line->delete();

        return back()->with('success', 'Membresía eliminada.');
    }
}
