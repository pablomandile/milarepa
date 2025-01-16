<?php

namespace App\Http\Controllers;

use App\Http\Requests\LinkRequest;
use Illuminate\Http\Request;
use App\Models\Stream;
use App\Models\Link;
use App\Http\Requests\StreamRequest;
use Inertia\Inertia;


class StreamsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $streams = Stream::with('links')->get();
       
        return inertia('Streams/Index', [
            'streams' => $streams->toArray()
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Streams/CreateFirstStep');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StreamRequest $request)
    {
        $stream = Stream::create($request->validated());

        return redirect()
        ->route('streams.edit', $stream->id)
        ->with('success', 'Stream creado con éxito. Ahora agrega Links.');
    }

    public function storeLink(LinkRequest $request, $id)
    {
        $stream = Stream::findOrFail($id);

       
        // Crea la fila en la tabla intermedia (esquema_precio_membresias)
        $stream->links()->create($request->validated());

        return redirect()->route('streams.edit', $id)
                        ->with('success', 'Se agregó el link al stream');
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
        $stream = Stream::with(['links'])->findOrFail($id);
    
        $links = Link::all();

        return Inertia::render('Streams/EditSecondStep', [
            'stream' => $stream,
            'links' => $links

        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    public function updateLink(LinkRequest $request, $linkId)
    {
       $line = Link::findOrFail($linkId);

       $line->update($request->validated());

       return back()->with('success', 'Link actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Stream $stream)
    {
        try {
            $stream->delete();
            return redirect()->route('streams.index')->with('sucsess', 'El stream se ha eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('streams.index')->with('error', 'Error al eliminar el stream: '. $e->getMessage());
        }
    }

    public function destroyLink($linkId)
    {
        $line = Link::findOrFail($linkId);
        $line->delete();

        return back()->with('success', 'Link  eliminado.');
    }

}
