<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ThemePreferenceController extends Controller
{
    public function update(Request $request): Response
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', 'in:light,dark'],
        ]);

        $user = $request->user();
        $user->forceFill(['theme_preference' => $validated['theme']])->save();

        return response()->noContent();
    }
}
