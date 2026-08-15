<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Moneda;
use App\Http\Requests\MonedaRequest;
use Inertia\Inertia;

class MonedasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $monedas = Moneda::get();
        return inertia('Monedas/Index', ['monedas' => $monedas]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Monedas/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MonedaRequest $request)
    {
        DB::transaction(function () use ($request) {
            $moneda = Moneda::create($request->validated());
            if ($moneda->es_principal) {
                $this->desmarcarOtrasPrincipales($moneda);
            }
        });
        return redirect()->route('monedas.index');
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
    public function edit(Moneda $moneda)
    {
        return Inertia::render('Monedas/Edit', [
            'moneda' => $moneda,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MonedaRequest $request, $id)
    {
        $moneda = Moneda::findOrFail($id);

        $data = $request->validated();

        // Siempre debe existir exactamente una moneda principal.
        if ($moneda->es_principal && array_key_exists('es_principal', $data) && !$data['es_principal']) {
            return back()->withErrors([
                'es_principal' => 'Debe existir una moneda principal: marcá otra como principal en su lugar.',
            ]);
        }

        DB::transaction(function () use ($moneda, $data) {
            $moneda->update($data);
            if ($moneda->es_principal) {
                $this->desmarcarOtrasPrincipales($moneda);
            }
        });

        return redirect()->route('monedas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    private function desmarcarOtrasPrincipales(Moneda $moneda): void
    {
        Moneda::where('id', '!=', $moneda->id)->where('es_principal', true)->update(['es_principal' => false]);
    }

    public function destroy($id)
    {
        try {
            $moneda = Moneda::findorfail($id);
            if ($moneda->es_principal) {
                return redirect()->route('monedas.index')->with('error', 'No se puede eliminar la moneda principal.');
            }
            $moneda->delete();
            return redirect()->route('monedas.index')->with('success', 'Moneda eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('monedas.index')->with('error', 'Error al eliminar la Moneda: ' . $e->getMessage());
        }
    }
}
