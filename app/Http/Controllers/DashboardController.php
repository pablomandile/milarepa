<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Models\Entidad;
use App\Models\Version;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(){
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    }

    public function dashboard(){
        $entidadPrincipal = Entidad::where('entidad_principal', true)->first();
        $version = Version::latest()->first();
        // dd($version);
        return Inertia::render('Dashboard', [
            'entidad_principal' => $entidadPrincipal ? $entidadPrincipal->nombre : 'Sin entidad principal configurada',
            'version' => $version ? [
                'version' => $version->version,
                'created_at' => $version->created_at 
                    ? Carbon::parse($version->created_at)->translatedFormat('F Y') // Formatea a "Enero 2025"
                    : null
            ] : null,
        ]);
    }
}
