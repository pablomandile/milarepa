<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\EstadoTicket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class InscripcionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inscripciones = Inscripcion::with([
                'actividad',
                'actividad.imagen',
                'actividad.entidad',
                'estado',
                'hospedaje',
                'comida',
                'transporte'
            ])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        // Agregar fecha formateada a las actividades
        $inscripciones->transform(function ($inscripcion) {
            $date = Carbon::parse($inscripcion->actividad->fecha_inicio);
            $inscripcion->actividad->fecha_inicio_formateada = $date->translatedFormat('j \d\e F H:i') . ' hs.';
            return $inscripcion;
        });

        return Inertia::render('Inscripciones/Index', [
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
        $request->validate([
            'actividad_id' => 'required|exists:actividades,id',
            // user_id lo tomamos del usuario autenticado para mayor seguridad
            'membresia' => 'required|string',
            'precioGeneral' => 'required|numeric',
            'montoapagar' => 'required|numeric',
            'pago' => 'required|in:total,parcial,impago',
            'estado_id' => 'required|exists:estados_ticket,id',
            'envioLinkStream' => 'required|in:enviado,pendiente',
            'envioGrabación' => 'required|in:enviada,pendiente',
            'comprobante' => 'nullable|string',
            'asistencia' => 'required|in:presente,ausente',
            'online' => 'required|boolean',
            'hospedaje_id' => 'nullable|exists:hospedajes,id',
            'comida_id' => 'nullable|exists:comidas,id',
            'transporte_id' => 'nullable|exists:transportes,id',
        ]);
        $userId = auth()->id();
        if (!$userId) {
            return back()->with('error', 'Debe iniciar sesión para inscribirse.');
        }

        // Evitar inscripciones duplicadas del mismo usuario a la misma actividad
        $actividadId = $request->input('actividad_id');
        $yaInscripto = Inscripcion::where('user_id', $userId)
            ->where('actividad_id', $actividadId)
            ->exists();

        if ($yaInscripto) {
            return back()->with('error', 'Ya está inscripto a esa actividad!');
        }

        // Crear inscripción usando el usuario autenticado
        $data = $request->all();
        $data['user_id'] = $userId;

        $inscripcion = Inscripcion::create($data);

        // Redirigir a la vista de detalle (show) de la Inscripción
        return redirect()->route('inscripciones.show', ['inscripcion' => $inscripcion->id])
            ->with('success', 'Inscripción creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $inscripcion = Inscripcion::with([
            'actividad.entidad',
            'actividad.descripcion',
            'actividad.imagen',
            'actividad.modalidad',
            'actividad.tipoActividad',
            'user',
            'estado',
            'hospedaje',
            'comida',
            'transporte',
        ])->findOrFail($id);

        // Verificar que la inscripción pertenece al usuario autenticado
        if ($inscripcion->user_id !== auth()->id()) {
            return back()->with('error', 'No autorizado a ver esta inscripción.');
        }

        // Formatear fecha de la actividad
        if (!empty($inscripcion->actividad) && !empty($inscripcion->actividad->fecha_inicio)) {
            try {
                $date = \Carbon\Carbon::parse($inscripcion->actividad->fecha_inicio);
                $inscripcion->actividad->fecha_inicio_formateada = $date->translatedFormat('j \d\e F H:i') . ' hs.';
            } catch (\Exception $e) {
                $inscripcion->actividad->fecha_inicio_formateada = $inscripcion->actividad->fecha_inicio;
            }
        }

        return Inertia::render('Inscripcion/Show', [
            'inscripcion' => $inscripcion,
        ]);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $inscripcion = Inscripcion::findOrFail($id);
        
        // Verificar que la inscripción pertenece al usuario autenticado
        if ($inscripcion->user_id !== auth()->id()) {
            return back()->withErrors(['message' => 'No autorizado']);
        }
        
        $inscripcion->delete();
        
        return back()->with('success', 'Inscripción eliminada correctamente');
    }
}
