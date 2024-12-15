<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Models\Entidad;


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
        return Inertia::render('Dashboard', [
            'entidad_principal' => $entidadPrincipal ? $entidadPrincipal->nombre : 'Sin entidad principal configurada',
        ]);
    }
}
