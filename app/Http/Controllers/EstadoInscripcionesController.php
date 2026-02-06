<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\EstadoActividad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstadoInscripcionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $estadoIds = EstadoActividad::whereIn('estado', ['Activa', 'Activo'])->pluck('id');

        $inscripciones = Inscripcion::with([
                'actividad',
                'actividad.entidad',
                'estado',
                'user',
                'user.pais',
                'user.provincia',
                'user.municipio',
                'user.barrio',
                'guestUser',
                'guestUser.pais',
                'guestUser.provincia',
                'guestUser.municipio',
                'guestUser.barrio',
                'auditorUser',
            ])
            ->when($estadoIds->count() > 0, function ($query) use ($estadoIds) {
                $query->whereIn('estado_id', $estadoIds);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('EstadoInscripciones/Index', [
            'inscripciones' => $inscripciones,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $data = $request->validate([
            'montoapagar' => ['required', 'numeric', 'min:0'],
            'pago' => ['required', 'in:Saldado,Parcial,Pendiente'],
        ]);

        $inscripcion = Inscripcion::findOrFail($id);
        $inscripcion->fill($data);
        $inscripcion->auditoria_fecha = now();
        $inscripcion->auditor = $user->id;
        $inscripcion->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
