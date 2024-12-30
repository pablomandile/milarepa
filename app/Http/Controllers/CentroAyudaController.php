<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ticket;



class CentroAyudaController extends Controller
{
      /**
     * Display a listing of the resource.
     */
    public function index()
    {
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
    // dd($tickets);
        return inertia('CentroAyuda/Index', [
            'tickets' => $tickets->values()->all(), // para reenviar como array ordenado
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
