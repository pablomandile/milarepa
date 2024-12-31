<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Http\Requests\TicketRequest;



class TicketsController extends Controller
{
      /**
     * Display a listing of the resource.
     */
    public function index()
    {
       

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

    public function asignar(Request $request, Ticket $ticket)
    {
        $request->validate([
            'responsable_id' => 'required|exists:users,id',
        ]);
        $ticket->estadoticket_id = 2;
        $ticket->responsable_id = $request->responsable_id;
        $ticket->save();

        // Podrías redireccionar, o retornar Inertia
        return back(); // O lo que necesites
    }
}
