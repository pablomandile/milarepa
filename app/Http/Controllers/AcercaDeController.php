<?php

namespace App\Http\Controllers;

use App\Models\Version;
use Inertia\Inertia;
use Inertia\Response;

class AcercaDeController extends Controller
{
    /**
     * Display the About page.
     */
    public function index(): Response
    {
        $version = Version::latest()->first();

        return Inertia::render('AcercaDe/Index', [
            'version' => $version ?? [
                'version' => 'Sin versión disponible',
                'created_at' => 'Fecha no especificada'
            ]
        ]);
    }
}
