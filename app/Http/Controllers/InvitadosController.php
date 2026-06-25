<?php

namespace App\Http\Controllers;

use App\Models\Invitado;
use Illuminate\Http\Request;

class InvitadosController extends Controller
{
    /**
     * Actualiza la asistencia individual de un invitado (gestión admin).
     */
    public function asistencia(Request $request, Invitado $invitado)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $data = $request->validate([
            'asistencia' => ['required', 'in:Presente,Ausente,Pendiente'],
        ]);

        $invitado->asistencia = $data['asistencia'];
        $invitado->save();

        return response()->json([
            'ok' => true,
            'asistencia' => $invitado->asistencia,
        ]);
    }
}
