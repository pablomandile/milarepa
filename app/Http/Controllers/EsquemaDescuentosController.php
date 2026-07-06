<?php

namespace App\Http\Controllers;

use App\Models\EsquemaDescuento;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Membresia;
use App\Models\Moneda;
use App\Http\Requests\EsquemaDescuentoRequest;
use Inertia\Inertia;
use App\Models\EsquemaDescuentoMembresia;
use App\Models\BotonPago;

class EsquemaDescuentosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $esquemadescuentos = EsquemaDescuento::with([
            'membresias.moneda',
            'membresias.membresia.entidad',  // <- Eager load de la Entidad
            'membresias.botonPago'
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
            'membresia_id' => [
                'required', 'exists:membresias,id',
                Rule::unique('esquema_descuento_membresias')
                    ->where(fn ($q) => $q->where('esquema_descuento_id', $id)),
            ],
            'botonpago_id' => 'nullable|exists:botones_pago,id',
            'precio' => 'required|numeric|min:0',
            'moneda_id' => 'required|exists:monedas,id'
        ], [
            'membresia_id.unique' => 'Esta membresía ya tiene un precio en este esquema.',
        ]);

        // Crea la fila en la tabla intermedia (esquema_descuento_membresias)
        $esquema->membresias()->create($validated);

        return redirect()->route('esquemadescuentos.edit', $id)
                        ->with('success', 'Se agregó la membresía al esquema');
    }

    /**
     * Copia la moneda + importe + botón de pago a todas las membresías de la misma
     * entidad (la de la membresía elegida) que todavía no tengan precio en el esquema.
     */
    public function storeMembresiasIguales(Request $request, $id)
    {
        $esquema = EsquemaDescuento::findOrFail($id);

        $validated = $request->validate([
            'membresia_id' => 'required|exists:membresias,id',
            'botonpago_id' => 'nullable|exists:botones_pago,id',
            'precio' => 'required|numeric|min:0',
            'moneda_id' => 'required|exists:monedas,id',
        ]);

        $entidadId = Membresia::whereKey($validated['membresia_id'])->value('entidad_id');

        $yaConPrecio = $esquema->membresias()->pluck('membresia_id')->all();
        $destino = Membresia::where('entidad_id', $entidadId)
            ->whereNotIn('id', $yaConPrecio)
            ->pluck('id');

        foreach ($destino as $membresiaId) {
            $esquema->membresias()->create([
                'membresia_id' => $membresiaId,
                'botonpago_id' => $validated['botonpago_id'] ?? null,
                'moneda_id'    => $validated['moneda_id'],
                'precio'       => $validated['precio'],
            ]);
        }

        $count = $destino->count();
        $mensaje = $count
            ? "Se agregaron {$count} membresía(s) con la misma moneda e importe."
            : 'Todas las membresías de la entidad ya tenían precio en este esquema.';

        return redirect()->route('esquemadescuentos.edit', $id)->with('success', $mensaje);
    }

    /**
     * "Gratis con TK": para cada entidad que ya tiene al menos un precio en el esquema,
     * agrega en $0 las membresías de esa entidad que todavía no tengan precio, usando la
     * misma moneda que ya usa la entidad y sin botón de pago.
     */
    public function storeMembresiasGratis($id)
    {
        $esquema = EsquemaDescuento::with('membresias.membresia')->findOrFail($id);

        $lineas = $esquema->membresias;
        $yaConPrecio = $lineas->pluck('membresia_id')->all();

        $monedaPorEntidad = [];
        foreach ($lineas as $linea) {
            $entidadId = $linea->membresia?->entidad_id;
            if ($entidadId && !isset($monedaPorEntidad[$entidadId])) {
                $monedaPorEntidad[$entidadId] = $linea->moneda_id;
            }
        }

        $destino = Membresia::whereIn('entidad_id', array_keys($monedaPorEntidad))
            ->whereNotIn('id', $yaConPrecio)
            ->get(['id', 'entidad_id']);

        foreach ($destino as $membresia) {
            $esquema->membresias()->create([
                'membresia_id' => $membresia->id,
                'botonpago_id' => null,
                'moneda_id'    => $monedaPorEntidad[$membresia->entidad_id],
                'precio'       => 0,
            ]);
        }

        $count = $destino->count();
        $mensaje = $count
            ? "Se agregaron {$count} membresía(s) en \$0 (gratis con TK)."
            : 'No hay membresías pendientes en las entidades que ya tienen precio.';

        return redirect()->route('esquemadescuentos.edit', $id)->with('success', $mensaje);
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
            'membresias.moneda',
            'membresias.botonPago'
        ])->findOrFail($id);
    
        $membresias = Membresia::with('entidad')->get()
                       ->map(function($m){
                           $m->label = $m->nombre . ' - ' . ($m->entidad->abreviacion ?? '');
                           return $m;
                       });
        $monedas = Moneda::select('id', 'nombre', 'simbolo')->get();
        $botonesPago = BotonPago::select('id', 'nombre')->get();
    
        return Inertia::render('EsquemaDescuentos/EditSecondStep', [
            'esquemaDescuento' => $esquemaDescuento,
            'membresias' => $membresias,
            'monedas' => $monedas,
            'botonesPago' => $botonesPago,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $esquemaDescuento = EsquemaDescuento::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
        ]);

        $esquemaDescuento->update($validated);

        return back()->with('success', 'Nombre del esquema actualizado.');
    }

    public function updateMembresia(Request $request, $membresiaId)
    {
       $line = EsquemaDescuentoMembresia::findOrFail($membresiaId);

       $validated = $request->validate([
           'membresia_id' => [
               'required', 'exists:membresias,id',
               Rule::unique('esquema_descuento_membresias')
                   ->where(fn ($q) => $q->where('esquema_descuento_id', $line->esquema_descuento_id))
                   ->ignore($line->id),
           ],
           'botonpago_id' => 'nullable|exists:botones_pago,id',
           'precio' => 'required|numeric|min:0',
           'moneda_id' => 'required|exists:monedas,id',
       ], [
           'membresia_id.unique' => 'Esta membresía ya tiene un precio en este esquema.',
       ]);

        $line->update($validated);

       return back()->with('success', 'Membresía actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EsquemaDescuento $esquemadescuento)
    {
        try {
            $esquemadescuento->delete();
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
