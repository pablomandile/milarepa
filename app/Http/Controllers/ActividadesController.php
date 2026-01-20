<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActividadRequest;
use Illuminate\Http\Request;
use App\Models\Actividad;
use App\Models\Comida;
use App\Models\Coordinador;
use App\Models\Descripcion;
use App\Models\Disponibilidad;
use App\Models\Entidad;
use App\Models\EsquemaDescuento;
use App\Models\EsquemaPrecio;
use App\Models\Grabacion;
use App\Models\Hospedaje;
use App\Models\Maestro;
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
            'descripcion',
            'imagen',
            'entidad',
            'disponibilidad',
            'modalidad',
            'esquemaPrecio.membresias.moneda',
            'esquemaPrecio.membresias.membresia.entidad',
            'esquemaDescuento',
            'stream',
            'grabacion',
            'programa',
            'metodosPago', 
            'hospedajes', 
            'comidas', 
            'transportes',
            'maestros',
            'coordinadores'
        ])->get();

        // dd($actividades);
        return inertia('Actividades/Index', ['actividades' => $actividades->toArray()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $esquema_precios = EsquemaPrecio::with([
            'membresias.moneda',
            'membresias.membresia.entidad'  // <- Eager load de la Entidad
        ])->get();
        $esquema_descuentos = EsquemaDescuento::with([
            'membresias.moneda',
            'membresias.membresia.entidad'  // <- Eager load de la Entidad
        ])->get();
        $tiposActividad = TipoActividad::all();
        $maestros = Maestro::all();
        $coordinadores = Coordinador::all();
        $descripciones = Descripcion::all();
        $entidades = Entidad::all();
        $disponibilidades = Disponibilidad::all();
        $modalidades = Modalidad::all();
        $streams = Stream::with([
            'links'
        ])->get();
        $grabaciones = Grabacion::with([
            'linksgrabacion'
        ])->get();
        $programas = Programa::all();
        $metodosPago = MetodoPago::all();
        $hospedajes = Hospedaje::all();
        $comidas = Comida::all();
        $transportes = Transporte::all();

        return inertia('Actividades/Create', [
            'tiposActividad' => $tiposActividad, 
            'descripciones' => $descripciones, 
            'entidades' => $entidades, 
            'maestros' => $maestros, 
            'coordinadores' => $coordinadores, 
            'disponibilidades' => $disponibilidades, 
            'modalidades' => $modalidades, 
            'esquema_precios' => $esquema_precios->toArray(),
            'esquema_descuentos' => $esquema_descuentos->toArray(), 
            'streams' => $streams, 
            'grabaciones' => $grabaciones, 
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
    public function store(ActividadRequest $request)
    {
        $validated = $request->validated();

        // Extraer arrays de ids (si tienes campos muchos-a-muchos) para luego sincronizar
        //    Nota: Tus rules no incluyen metodos_pago_ids, etc. 
        //    Por lo tanto, podrías manejarlo manualmente.
        $metodosPagoIds = $request->input('metodos_pago_ids', []);
        $hospedajesIds  = $request->input('hospedajes_ids', []);
        $comidasIds     = $request->input('comidas_ids', []);
        $transportesIds = $request->input('transportes_ids', []);
        $maestrosIds = $request->input('maestros_ids', []);
        $coordinadoresIds = $request->input('coordinadores_ids', []);

        // Si metodos_pago_ids viene vacío (por ejemplo null o un array vacío), 
        // lo forzamos a [1] como valor por defecto: Efectivo
        if (empty($metodosPagoIds)) {
            $metodosPagoIds = [1];
        }
 

        // Quitar del array $validated los que no estén en la BD si no vas a guardarlos como columna
        //    (o si no definiste esas columnas en $fillable)
        unset($validated['metodos_pago_ids'], $validated['hospedajes_ids'],
              $validated['comidas_ids'], $validated['transportes_ids'],
              $validated['maestros_ids'], $validated['coordinadores_ids']);

        $actividad = Actividad::create($validated);

        // 5) Manejar la parte de "muchos a muchos" si es que tus modelos y pivots existen
        //    Suponiendo que en tu modelo Actividad tienes:
        //    public function metodosPago() { return $this->belongsToMany(MetodoPago::class); }
        //    y lo mismo para hospedajes, comidas, transportes, etc.
           $actividad->metodosPago()->sync($metodosPagoIds);
           $actividad->hospedajes()->sync($hospedajesIds);
           $actividad->comidas()->sync($comidasIds);
           $actividad->transportes()->sync($transportesIds);
           $actividad->maestros()->sync($maestrosIds);
           $actividad->coordinadores()->sync($coordinadoresIds);


           return redirect()->route('actividades.index')
           ->with('success', 'Actividad creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $actividad = Actividad::with([
            'tipoActividad',
            'descripcion',
            'imagen',
            'entidad',
            'disponibilidad',
            'modalidad',
            'esquemaPrecio',
            'esquemaDescuento',
            'stream',
            'grabacion',
            'programa',
            'metodosPago',
            'hospedajes',
            'comidas',
            'transportes',
            'maestros',
            'coordinadores'
        ])->findOrFail($id);

        // Fecha formateada de inicio si existe
        if (!empty($actividad->fecha_inicio)) {
            try {
                $date = \Carbon\Carbon::parse($actividad->fecha_inicio);
                $actividad->fecha_inicio_formateada = $date->translatedFormat('j \d\e F H:i') . ' hs.';
            } catch (\Exception $e) {
                $actividad->fecha_inicio_formateada = $actividad->fecha_inicio;
            }
        }

        return inertia('Actividades/Show', [
            'actividad' => $actividad->toArray(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
            $actividad = Actividad::with([
                'tipoActividad',
                'descripcion',
                'imagen',
                'entidad',
                'disponibilidad',
                'modalidad',
                'esquemaPrecio',
                'esquemaDescuento',
                'stream',
                'grabacion',
                'programa',
                'metodosPago',
                'hospedajes',
                'comidas',
                'transportes',
                'maestros',
                'coordinadores'
            ])->findOrFail($id);

            // Obtener todos los catálogos necesarios para el formulario
            $esquema_precios = EsquemaPrecio::with([
                'membresias.moneda',
                'membresias.membresia.entidad'
            ])->get();
            $esquema_descuentos = EsquemaDescuento::with([
                'membresias.moneda',
                'membresias.membresia.entidad'
            ])->get();
            $tiposActividad = TipoActividad::all();
            $maestros = Maestro::all();
            $coordinadores = Coordinador::all();
            $descripciones = Descripcion::all();
            $entidades = Entidad::all();
            $disponibilidades = Disponibilidad::all();
            $modalidades = Modalidad::all();
            $streams = Stream::with(['links'])->get();
            $grabaciones = Grabacion::with(['linksgrabacion'])->get();
            $programas = Programa::all();
            $metodosPago = MetodoPago::all();
            $hospedajes = Hospedaje::all();
            $comidas = Comida::all();
            $transportes = Transporte::all();

            return inertia('Actividades/Edit', [
                'actividad' => $actividad,
                'tiposActividad' => $tiposActividad,
                'descripciones' => $descripciones,
                'entidades' => $entidades,
                'disponibilidades' => $disponibilidades,
                'modalidades' => $modalidades,
                'esquema_precios' => $esquema_precios->toArray(),
                'esquema_descuentos' => $esquema_descuentos->toArray(),
                'streams' => $streams,
                'grabaciones' => $grabaciones,
                'programas' => $programas,
                'metodosPago' => $metodosPago,
                'hospedajes' => $hospedajes,
                'comidas' => $comidas,
                'transportes' => $transportes,
                'maestros' => $maestros,
                'coordinadores' => $coordinadores
            ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ActividadRequest $request, string $id)
    {
        $actividad = Actividad::findOrFail($id);
        
        $validated = $request->validated();

        // Extraer arrays de ids para relaciones muchos-a-muchos
        $metodosPagoIds = $request->input('metodos_pago_ids', []);
        $hospedajesIds  = $request->input('hospedajes_ids', []);
        $comidasIds     = $request->input('comidas_ids', []);
        $transportesIds = $request->input('transportes_ids', []);
        $maestrosIds = $request->input('maestros_ids', []);
        $coordinadoresIds = $request->input('coordinadores_ids', []);

        if (empty($metodosPagoIds)) {
            $metodosPagoIds = [1];
        }

        // Quitar del array validated los que no están en fillable
        unset($validated['metodos_pago_ids'], $validated['hospedajes_ids'],
              $validated['comidas_ids'], $validated['transportes_ids'],
              $validated['maestros_ids'], $validated['coordinadores_ids']);

        // Actualizar la actividad
        $actividad->update($validated);

        // Sincronizar relaciones muchos-a-muchos
        $actividad->metodosPago()->sync($metodosPagoIds);
        $actividad->hospedajes()->sync($hospedajesIds);
        $actividad->comidas()->sync($comidasIds);
        $actividad->transportes()->sync($transportesIds);
        $actividad->maestros()->sync($maestrosIds);
        $actividad->coordinadores()->sync($coordinadoresIds);

        return redirect()->route('actividades.index')
            ->with('success', 'Actividad actualizada correctamente.');
    }

    /**
     * Quick toggle for estado field only.
     */
    public function updateEstado(Request $request, Actividad $actividad)
    {
        $data = $request->validate([
            'estado' => ['required', 'boolean'],
        ]);

        $actividad->estado = $data['estado'];
        $actividad->save();

        return redirect()->back()->with('success', 'Estado actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $actividad = Actividad::findOrFail($id);
        
        // Eliminar la actividad
        $actividad->delete();
        
        return redirect()->route('actividades.index')
            ->with('success', 'Actividad eliminada correctamente.');
    }
}
