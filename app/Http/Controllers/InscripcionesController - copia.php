<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\InscripcionComprobante;
use App\Models\EstadoActividad;
use App\Mail\InscripcionConfirmada;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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
            'transporte',
            'comprobantes'
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
            'pago' => 'required|in:Saldado,Parcial,Pendiente',
            'estado_id' => 'required|exists:estados_actividad,id',
            'envioLinkStream' => 'required|in:enviado,pendiente',
            'envioGrabación' => 'required|in:enviada,pendiente',
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

        // Crear Inscripci�n usando el usuario autenticado
        $data = $request->all();
        $data['user_id'] = $userId;

        $inscripcion = Inscripcion::create($data);

        // Cargar relaciones para el email
        $inscripcion->load([
            'actividad.entidad',
            'actividad.descripcion',
            'actividad.modalidad',
            'user',
            'estado',
            'comprobantes'
        ]);

        // Enviar email de confirmación
        $emailEnviado = false;
        $mensajeEmail = '';
        
        try {
            Mail::to($inscripcion->user->email)->send(new InscripcionConfirmada($inscripcion));
            $emailEnviado = true;
            $mensajeEmail = 'Email de confirmación enviado correctamente a ' . $inscripcion->user->email;
            Log::info('Email de Inscripci�n enviado correctamente', [
                'inscripcion_id' => $inscripcion->id,
                'user_email' => $inscripcion->user->email
            ]);
        } catch (\Exception $e) {
            $emailEnviado = false;
            $mensajeEmail = 'Advertencia: No se pudo enviar el email de confirmación. Error: ' . $e->getMessage();
            Log::error('Error al enviar email de Inscripci�n', [
                'inscripcion_id' => $inscripcion->id,
                'user_email' => $inscripcion->user->email,
                'error' => $e->getMessage(),
                'exception' => get_class($e)
            ]);
        }

        // Redirigir a la vista de detalle (show) de la Inscripci�n
        return redirect()->route('inscripciones.show', ['inscripcion' => $inscripcion->id])
            ->with('success', 'Inscripci�n creada correctamente.')
            ->with('email_status', [
                'enviado' => $emailEnviado,
                'mensaje' => $mensajeEmail
            ]);
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
            'comprobantes',
        ])->findOrFail($id);

        // Verificar que la Inscripci�n pertenece al usuario autenticado
        if ($inscripcion->user_id !== auth()->id()) {
            return back()->with('error', 'No autorizado a ver esta Inscripci�n.');
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
     * Display a mobile ticket view for the given Inscripci�n.
     */
    public function ticket(string $id)
    {
        $inscripcion = Inscripcion::with([
            'actividad.imagen',
            'actividad.entidad',
            'actividad.programa',
        ])->findOrFail($id);

        // Authorize ownership
        if ($inscripcion->user_id !== auth()->id()) {
            return back()->with('error', 'No autorizado a ver esta Inscripci�n.');
        }

        // Format activity start date
        if (!empty($inscripcion->actividad) && !empty($inscripcion->actividad->fecha_inicio)) {
            try {
                $date = \Carbon\Carbon::parse($inscripcion->actividad->fecha_inicio);
                $inscripcion->actividad->fecha_inicio_formateada = $date->translatedFormat('j \\d\\e F H:i') . ' hs.';
            } catch (\Exception $e) {
                $inscripcion->actividad->fecha_inicio_formateada = $inscripcion->actividad->fecha_inicio;
            }
        }

        return Inertia::render('Inscripciones/Ticket', [
            'inscripcion' => $inscripcion,
            'actividad' => $inscripcion->actividad,
        ]);
    }

    /**
     * Mark attendance for the given Inscripci�n via a signed URL.
     */
    public function asistir(string $id)
    {
        $inscripcion = Inscripcion::with(['actividad'])->findOrFail($id);

        // Optionally: require auth of staff; here we rely on signed URL
        // Update asistencia only if not already present
        if ($inscripcion->asistencia !== 'presente') {
            $inscripcion->asistencia = 'presente';
            $inscripcion->save();
        }

        return Inertia::render('Inscripciones/ConfirmAsistencia', [
            'inscripcion' => $inscripcion,
            'actividad' => $inscripcion->actividad,
            'status' => 'ok',
        ]);
    }

    /**
     * Serve a QR image (SVG) for the asistencia signed URL.
     */
    public function ticketQr(string $id)
    {
        $inscripcion = Inscripcion::findOrFail($id);

        // Only allow generating QR for the owner; the ticket page uses this
        if ($inscripcion->user_id !== auth()->id()) {
            abort(403);
        }

        // Create a temporary signed URL for marking attendance
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'inscripciones.asistir',
            now()->addHours(12),
            ['inscripcion' => $inscripcion->id]
        );

        // Generate SVG QR using BaconQrCode
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(256),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $svg = $writer->writeString($url);

        return response($svg, 200)->header('Content-Type', 'image/svg+xml');
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
        
        // Verificar que la Inscripci�n pertenece al usuario autenticado
        if ($inscripcion->user_id !== auth()->id()) {
            return back()->withErrors(['message' => 'No autorizado']);
        }
        
        $inscripcion->delete();
        
        return back()->with('success', 'Inscripci�n eliminada correctamente');
    }

    public function uploadComprobante(Request $request, Inscripcion $inscripcion)
    {
        $user = $request->user();
        if (!$user) {
            return back()->withErrors(['message' => 'No autorizado']);
        }

        $esOwner = $inscripcion->user_id === $user->id;
        $esAdmin = $user->hasRole(['Admin', 'Editor', 'admin', 'editor']);

        if (!$esOwner && !$esAdmin) {
            return back()->withErrors(['message' => 'No autorizado']);
        }

        $request->validate([
            'comprobante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ], [
            'comprobante.max' => 'El comprobante supera el tama�o m�ximo permitido (4 MB).',
            'comprobante.mimes' => 'El comprobante debe ser PDF, JPG o PNG.',
        ]);

        $path = $request->file('comprobante')->store('comprobantes', 'public');
        InscripcionComprobante::create([
            'inscripcion_id' => $inscripcion->id,
            'ruta' => $path,
            'descripcion' => $request->input('descripcion'),
        ]);

        return back()->with('success', 'Comprobante subido correctamente.');
    }
}






