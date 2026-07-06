<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\EsquemaPrecio;
use App\Models\Membresia;
use App\Models\Moneda;
use App\Http\Requests\EsquemaPrecioRequest;
use Inertia\Inertia;
use App\Models\EsquemaPrecioMembresia;
use App\Models\BotonPago;


class EsquemaPreciosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $esquemaprecios = EsquemaPrecio::with([
            'membresias.moneda',
            'membresias.membresia.entidad',  // <- Eager load de la Entidad
            'membresias.botonPago'
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
            'membresia_id' => [
                'required', 'exists:membresias,id',
                Rule::unique('esquema_precio_membresias')
                    ->where(fn ($q) => $q->where('esquema_precio_id', $id)),
            ],
            'botonpago_id' => 'nullable|exists:botones_pago,id',
            'precio' => 'required|numeric|min:0',
            'moneda_id' => 'required|exists:monedas,id'
        ], [
            'membresia_id.unique' => 'Esta membresía ya tiene un precio en este esquema.',
        ]);

        // Crea la fila en la tabla intermedia (esquema_precio_membresias)
        $esquema->membresias()->create($validated);

        return redirect()->route('esquemaprecios.edit', $id)
                        ->with('success', 'Se agregó la membresía al esquema');
    }

    /**
     * Copia la moneda + importe + botón de pago a todas las membresías de la misma
     * entidad (la de la membresía elegida) que todavía no tengan precio en el esquema.
     */
    public function storeMembresiasIguales(Request $request, $id)
    {
        $esquema = EsquemaPrecio::findOrFail($id);

        $validated = $request->validate([
            'membresia_id' => 'required|exists:membresias,id',
            'botonpago_id' => 'nullable|exists:botones_pago,id',
            'precio' => 'required|numeric|min:0',
            'moneda_id' => 'required|exists:monedas,id',
        ]);

        // Entidad de la membresía seleccionada
        $entidadId = Membresia::whereKey($validated['membresia_id'])->value('entidad_id');

        // Membresías de esa entidad que aún no tienen precio en este esquema
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

        return redirect()->route('esquemaprecios.edit', $id)->with('success', $mensaje);
    }

    /**
     * "Gratis con TK": para cada entidad que ya tiene al menos un precio en el esquema,
     * agrega en $0 las membresías de esa entidad que todavía no tengan precio, usando la
     * misma moneda que ya usa la entidad y sin botón de pago.
     */
    public function storeMembresiasGratis($id)
    {
        $esquema = EsquemaPrecio::with('membresias.membresia')->findOrFail($id);

        $lineas = $esquema->membresias;
        $yaConPrecio = $lineas->pluck('membresia_id')->all();

        // Moneda representativa por entidad (la de su primera línea existente)
        $monedaPorEntidad = [];
        foreach ($lineas as $linea) {
            $entidadId = $linea->membresia?->entidad_id;
            if ($entidadId && !isset($monedaPorEntidad[$entidadId])) {
                $monedaPorEntidad[$entidadId] = $linea->moneda_id;
            }
        }

        // Membresías faltantes de esas entidades
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

        return redirect()->route('esquemaprecios.edit', $id)->with('success', $mensaje);
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
    
        return Inertia::render('EsquemaPrecios/EditSecondStep', [
            'esquemaPrecio' => $esquemaPrecio,
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
        $esquemaPrecio = EsquemaPrecio::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
        ]);

        $esquemaPrecio->update($validated);

        return back()->with('success', 'Nombre del esquema actualizado.');
    }

    public function updateMembresia(Request $request, $membresiaId)
    {
       $line = EsquemaPrecioMembresia::findOrFail($membresiaId);

       $validated = $request->validate([
           'membresia_id' => [
               'required', 'exists:membresias,id',
               Rule::unique('esquema_precio_membresias')
                   ->where(fn ($q) => $q->where('esquema_precio_id', $line->esquema_precio_id))
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
