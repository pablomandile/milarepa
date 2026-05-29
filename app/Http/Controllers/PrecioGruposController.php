<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrecioGrupoRequest;
use App\Models\Membresia;
use App\Models\PrecioGrupo;
use App\Models\PrecioGrupoMembresia;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrecioGruposController extends Controller
{
    public function index()
    {
        $precioGrupos = PrecioGrupo::with(['lineas.membresia.entidad'])
            ->orderBy('fecha_desde', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('PrecioGrupos/Index', [
            'precioGrupos' => $precioGrupos->toArray(),
        ]);
    }

    public function create()
    {
        return Inertia::render('PrecioGrupos/CreateFirstStep');
    }

    public function store(PrecioGrupoRequest $request)
    {
        $precioGrupo = PrecioGrupo::create($request->validated());

        return redirect()
            ->route('precio-grupos.edit', $precioGrupo->id)
            ->with('success', 'Grupo de precios creado. Ahora cargá los precios por membresía.');
    }

    public function edit(string $id)
    {
        $precioGrupo = PrecioGrupo::with(['lineas.membresia.entidad'])->findOrFail($id);

        $membresias = Membresia::with('entidad')
            ->where('nombre', '!=', 'Sin membresía')
            ->orderBy('nombre')
            ->get()
            ->map(function ($m) {
                $m->label = $m->nombre . ' - ' . ($m->entidad->abreviacion ?? '');
                return $m;
            });

        return Inertia::render('PrecioGrupos/EditSecondStep', [
            'precioGrupo' => $precioGrupo,
            'membresias' => $membresias,
        ]);
    }

    public function storeMembresia(Request $request, $id)
    {
        $precioGrupo = PrecioGrupo::findOrFail($id);

        $validated = $request->validate([
            'membresia_id' => 'required|exists:membresias,id',
            'valor' => 'required|numeric|min:0',
        ]);

        try {
            $precioGrupo->lineas()->create($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with('error', 'Esa membresía ya tiene un precio en este grupo. Editala en la tabla.');
            }
            throw $e;
        }

        return redirect()
            ->route('precio-grupos.edit', $id)
            ->with('success', 'Precio agregado al grupo.');
    }

    public function updateMembresia(Request $request, $lineaId)
    {
        $linea = PrecioGrupoMembresia::findOrFail($lineaId);

        $validated = $request->validate([
            'membresia_id' => 'required|exists:membresias,id',
            'valor' => 'required|numeric|min:0',
        ]);

        $linea->update($validated);

        return back()->with('success', 'Precio actualizado.');
    }

    public function destroyMembresia($lineaId)
    {
        $linea = PrecioGrupoMembresia::findOrFail($lineaId);
        $linea->delete();

        return back()->with('success', 'Línea eliminada.');
    }

    public function update(Request $request, PrecioGrupo $precioGrupo)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        $precioGrupo->update($validated);

        return back()->with('success', 'Nombre del grupo actualizado.');
    }

    public function destroy(PrecioGrupo $precioGrupo)
    {
        try {
            $precioGrupo->delete();
            return redirect()
                ->route('precio-grupos.index')
                ->with('success', 'Grupo de precios eliminado.');
        } catch (\Exception $e) {
            return redirect()
                ->route('precio-grupos.index')
                ->with('error', 'Error al eliminar el grupo: ' . $e->getMessage());
        }
    }

    public function aplicar(PrecioGrupo $precioGrupo)
    {
        $precioGrupo->load('lineas');

        $afectadas = 0;
        foreach ($precioGrupo->lineas as $linea) {
            Membresia::where('id', $linea->membresia_id)->update(['valor' => $linea->valor]);
            $afectadas++;
        }

        return redirect()
            ->back()
            ->with('success', "Precios actualizados desde el grupo '{$precioGrupo->nombre}'. {$afectadas} membresías afectadas.");
    }
}
