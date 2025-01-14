<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Actividad;
use App\Models\Comida;
use App\Models\Descripcion;
use App\Models\Disponibilidad;
use App\Models\Entidad;
use App\Models\EsquemaDescuento;
use App\Models\EsquemaPrecio;
use App\Models\Hospedaje;
use App\Models\MetodoPago;
use App\Models\Modalidad;
use App\Models\Programa;
use App\Models\Stream;
use App\Models\TipoActividad;
use App\Models\Transporte;

class ActividadesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $actividades = Actividad::with([
            'tipoActividad',
            'descripcion_id',
            'entidad',
            'disponibilidad',
            'modalidad',
            'esquemaPrecio',
            'esquemaDescuento',
            'stream',
            'programa',
        ]);

        return inertia('Actividades/Index', ['actividades' => $actividades]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tiposActividad = TipoActividad::all();
        $descripciones = Descripcion::all();
        $entidades = Entidad::all();
        $disponibilidades = Disponibilidad::all();
        $modalidades = Modalidad::all();
        $esquema_precios = EsquemaPrecio::all();
        $esquema_descuentos = EsquemaDescuento::all();
        $streams = Stream::all();
        $programas = Programa::all();
        $metodosPago = MetodoPago::all();
        $hospedajes = Hospedaje::all();
        $comidas = Comida::all();
        $transportes = Transporte::all();

        return inertia('Actividades/Create', [
            'tiposActividad' => $tiposActividad, 
            'descripciones' => $descripciones, 
            'entidades' => $entidades, 
            'disponibilidades' => $disponibilidades, 
            'modalidades' => $modalidades, 
            'esquema_precios' => $esquema_precios, 
            'esquema_descuentos' => $esquema_descuentos, 
            'streams' => $streams, 
            'programas' => $programas,
            'metodosPago' => $metodosPago,
            'hospedajes' => $hospedajes,
            'comidas' => $comidas,
            'transportes' => $transportes
        ]);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
