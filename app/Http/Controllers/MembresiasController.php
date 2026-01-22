<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Membresia;
use App\Http\Requests\MembresiaRequest;
use Inertia\Inertia;
use App\Models\Entidad;


class MembresiasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Obtener la entidad principal
        $entidadPrincipal = Entidad::where('entidad_principal', true)->first();

        if ($entidadPrincipal) {
            $membresias = Membresia::with('entidad')
                ->where('entidad_id', $entidadPrincipal->id)
                ->where('nombre', '!=', 'Sin membresía')
                ->paginate(10);
        } else {
            // Si no hay entidad principal, mostrar todas las membresías excepto "Sin membresía"
            $membresias = Membresia::with('entidad')
                ->where('nombre', '!=', 'Sin membresía')
                ->paginate(10);
        }

        // Obtener membresía actual del usuario
        $userMembresia = null;
        if (auth()->check() && auth()->user()->membresia_id) {
            $userMembresia = Membresia::find(auth()->user()->membresia_id);
        }

        return inertia('Membresias/Index', [
            'membresias' => $membresias,
            'user_membresia' => $userMembresia
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $entidades = Entidad::select('id','nombre')->get();

        return inertia('Membresias/Create', [
            'entidades' => $entidades
        ]);
    }

       /**
     * Store a newly created resource in storage.
     * @param App\Http\Requests\MembresiaRequest
     * @param \Illuminate\Http\Response
     */
    public function store(MembresiaRequest $request)
    {
        Membresia::create($request->validated());
        return redirect()->route('membresias.gestion')->with('success', 'Membresía creada con éxito.');
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
    public function edit(Membresia $membresia)
    {
        $entidades = Entidad::select('id','nombre')->get();

        // Devolver la vista de edición
        return Inertia::render('Membresias/Edit', ['membresia' => $membresia, 'entidades' => $entidades]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MembresiaRequest $request, $id)
    {
        $membresia = Membresia::findOrFail($id);

        $membresia->update($request->validated());

        return redirect()->route('membresias.gestion')->with('success', 'Membresía actualizada con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $membresia = Membresia::findorfail($id);
            $membresia->delete();
            return redirect()->route('membresias.index')->with('success', 'Membresía eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('membresias.index')->with('error', 'Error al eliminar la Membresía: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of the resource for admin management.
     */
    public function gestion()
    {
        $membresias = Membresia::with('entidad')->paginate(10);

        return inertia('Membresias/Gestion', ['membresias' => $membresias]);
    }

    /**
     * Subscribe a user to a membership
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'membresia_id' => 'required|exists:membresias,id'
        ]);

        $user = auth()->user();
        $user->update(['membresia_id' => $request->membresia_id]);

        return redirect()->route('membresias.index')->with('success', 'Te has inscrito correctamente a la membresía');
    }
}
