<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;

class AsistenciasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $asistencias = Asistencia::with([
            'inscripcion:id,actividad_id,user_id,estado',
            'inscripcion.actividad:id,nombre',
            'inscripcionClase:id,clase_id,user_id,membresia,pago',
            'inscripcionClase.clase:id,nombre',
            'inscripcionClase.usuario:id,name,email',
            'usuario:id,name,email',
        ])->latest('id')->get();

        return inertia('Asistencias/Index', [
            'asistencias' => $asistencias,
        ]);
    }
}
