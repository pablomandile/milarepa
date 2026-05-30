<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        $desarrollador = User::where('email', 'pablo.mandile@bue.edu.ar')
            ->orWhere('email', 'pablo.mandile@gmail.com')
            ->first();

        return Inertia::render('AcercaDe/Index', [
            'version' => $version ?? [
                'version' => 'Sin versión disponible',
                'created_at' => 'Fecha no especificada'
            ],
            'desarrollador' => [
                'name' => 'Pablo Mandile',
                'email' => 'pablo.mandile@gmail.com',
                'profile_photo_url' => $desarrollador?->profile_photo_url
                    ?? 'https://ui-avatars.com/api/?name=Pablo+Mandile&background=7c3aed&color=ffffff&size=256&bold=true',
            ],
        ]);
    }
}
