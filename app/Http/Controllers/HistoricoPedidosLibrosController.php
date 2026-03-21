<?php

namespace App\Http\Controllers;

use App\Models\HistoricoPedidoLibro;
use Inertia\Response;

class HistoricoPedidosLibrosController extends Controller
{
    public function index(): Response
    {
        $historicoPedidos = HistoricoPedidoLibro::with([
            'libro:id,titulo',
            'vendedor:id,name,email',
        ])->latest('fecha')->get();

        return inertia('HistoricoPedidosLibros/Index', [
            'historicoPedidos' => $historicoPedidos,
        ]);
    }
}
