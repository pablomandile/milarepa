<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Models\Entidad;
use App\Models\FraseDeDharma;
use App\Models\Version;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(){
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    }

    public function dashboard(){
        $frase = FraseDeDharma::inRandomOrder()->first();

        return Inertia::render('Dashboard', [
            'frase' => $frase ? [
                'cita_textual' => $frase->cita_textual,
                'libro' => $frase->libro,
            ] : null,
        ]);
    }
}
