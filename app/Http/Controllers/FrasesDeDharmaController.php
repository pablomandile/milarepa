<?php

namespace App\Http\Controllers;

use App\Models\FraseDeDharma;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FrasesDeDharmaController extends Controller
{
    public function index(): Response
    {
        $frases = FraseDeDharma::orderBy('numero')->get(['id', 'numero', 'cita_textual', 'libro']);

        return Inertia::render('FrasesDeDharma/Index', [
            'frases' => $frases,
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('archivo')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'No se pudo abrir el archivo.');
        }

        // Header (descartar y quitar BOM si existe)
        $header = fgetcsv($handle);
        if ($header !== false && isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        $now = now();
        $rows = [];
        $invalidRows = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 3) {
                $invalidRows++;
                continue;
            }

            [$numero, $cita, $libro] = $data;
            $cita = trim((string) $cita);
            $libro = trim((string) $libro);

            if ($cita === '' || $libro === '') {
                $invalidRows++;
                continue;
            }

            $rows[] = [
                'numero' => (int) $numero,
                'cita_textual' => self::fixEncoding($cita),
                'libro' => self::fixEncoding($libro),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        fclose($handle);

        if (empty($rows)) {
            return back()->with('error', 'El archivo no tiene filas válidas.');
        }

        FraseDeDharma::insert($rows);

        $msg = 'Se importaron ' . count($rows) . ' frase(s).';
        if ($invalidRows > 0) {
            $msg .= ' ' . $invalidRows . ' fila(s) fueron ignoradas por formato inválido.';
        }

        return back()->with('success', $msg);
    }

    public function destroy(FraseDeDharma $fraseDeDharma): RedirectResponse
    {
        $fraseDeDharma->delete();
        return back()->with('success', 'Frase eliminada.');
    }

    /**
     * Normaliza UTF-8 doblemente codificado (Ã³, Â«, etc.) si aparece.
     */
    private static function fixEncoding(string $text): string
    {
        $hasMojibake = preg_match('/Ã[©³¡­ºñ¼]|Â«|Â»/u', $text) === 1;
        if (!$hasMojibake) {
            return $text;
        }

        $fixed = @mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        $fixed = @mb_convert_encoding($fixed, 'UTF-8', 'ISO-8859-1');
        return is_string($fixed) && $fixed !== '' ? $fixed : $text;
    }
}
