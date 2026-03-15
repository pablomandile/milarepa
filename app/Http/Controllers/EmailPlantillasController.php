<?php

namespace App\Http\Controllers;

use App\Models\EmailPlantilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class EmailPlantillasController extends Controller
{
    public function index()
    {
        $emails = EmailPlantilla::orderBy('nombre')->get();

        return inertia('Emails/Index', [
            'emails' => $emails,
        ]);
    }

    public function create()
    {
        return inertia('Emails/Create', [
            'templateFiles' => $this->getTemplateFiles(),
        ]);
    }

    public function store(Request $request)
    {
        $templateFiles = $this->getTemplateFiles();

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'plantilla_archivo' => ['required', 'string', Rule::in($templateFiles)],
        ]);

        EmailPlantilla::create($validated);

        return redirect()->route('emails.index')->with('success', 'Email creado con exito.');
    }

    public function edit(EmailPlantilla $email)
    {
        return inertia('Emails/Edit', [
            'email' => $email,
            'templateFiles' => $this->getTemplateFiles(),
        ]);
    }

    public function update(Request $request, EmailPlantilla $email)
    {
        $templateFiles = $this->getTemplateFiles();

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'plantilla_archivo' => ['required', 'string', Rule::in($templateFiles)],
        ]);

        $email->update($validated);

        return redirect()->route('emails.index')->with('success', 'Email actualizado con exito.');
    }

    public function destroy(EmailPlantilla $email)
    {
        $email->delete();

        return redirect()->route('emails.index')->with('success', 'Email eliminado con exito.');
    }

    private function getTemplateFiles(): array
    {
        $directory = resource_path('views/emails');
        if (!File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->map(fn ($file) => $file->getFilename())
            ->filter(fn ($name) => str_ends_with($name, '.blade.php'))
            ->values()
            ->all();
    }
}
