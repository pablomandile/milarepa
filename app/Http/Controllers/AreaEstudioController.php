<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class AreaEstudioController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $programa = $user->programaEstudio()
            ->with(['programaGrabaciones' => function ($q) {
                $q->orderByDesc('created_at');
            }])
            ->first();

        return Inertia::render('AreaEstudio/Index', [
            'programa' => $programa,
            'grabaciones' => $programa?->programaGrabaciones ?? [],
        ]);
    }
}
