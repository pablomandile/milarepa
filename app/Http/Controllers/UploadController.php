<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function index()
    {
    }

    public function create()
    {
    }

    public function store(Request $request)
    {
        $file = $request->file('file') ?? $request->file('imagen');

        if (!$file) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $folder = (string) $request->input('folder', 'actividades');
        $allowedFolders = ['actividades', 'img/puyas'];

        if (!in_array($folder, $allowedFolders, true)) {
            $folder = 'actividades';
        }

        $path = $file->store($folder, 'public');

        return response()->json(['filePath' => Storage::url($path)], 200);
    }

    public function show(string $id)
    {
        //
    }

    public function edit()
    {
    }

    public function update()
    {
    }

    public function destroy()
    {
    }
}
