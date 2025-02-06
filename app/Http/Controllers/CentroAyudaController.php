<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Http\Requests\TicketRequest;
use App\Models\EstadoTicket;
use App\Models\User;



class CentroAyudaController extends Controller
{
      /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Usuarios que pueden ser responsables (admin o editor)
        // Usando Spatie: hasAnyRole(['admin','editor'])
        $usuariosResponsables = User::role(['admin','editor'])->get();

        if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('editor')) {
            $tickets = Ticket::with('estadoTicket', 'user', 'responsable')
                ->get()
                ->sortBy(fn($ticket) => $ticket->estadoTicket->estado);
        } else {
            $tickets = Ticket::with('estadoTicket', 'user', 'responsable')
                ->where('user_id', auth()->id())
                ->get()
                ->sortBy(fn($ticket) => $ticket->estadoTicket->estado);
        }
        return inertia('CentroAyuda/Index', [
            'tickets' => $tickets->values()->all(), // para reenviar como array ordenado
            'usuariosResponsables' => $usuariosResponsables,
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('CentroAyuda/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketRequest $request)
    {
        Ticket::create($request->validated());
        return redirect()-> route('centroayuda.index');
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
        $ticket = Ticket::with(['estadoTicket','user','responsable'])->findOrFail($id);
        $usuariosResponsables = User::role(['admin','editor'])->get();
        $estados = EstadoTicket::select('id','estado')->get();

        // Insertamos la opción "Sin responsable"
        $usuariosResponsables->prepend([
            'id' => null,
            'name' => 'Sin responsable',
        ]);

        return inertia::render('CentroAyuda/Edit', 
        ['ticket'=>$ticket, 
        'usuariosResponsables' => $usuariosResponsables,
        'estados' => $estados
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketRequest $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $ticket->update($request->validated());

        return redirect()->route('centroayuda.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $ticket = Ticket::findorfail($id);
            $ticket->delete();
            return redirect()->route('centroayuda.index')->with('success', 'Ticket eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('centroayuda.index')->with('error', 'Error al eliminar el Ticket: ' . $e->getMessage());
        }
    }

}
