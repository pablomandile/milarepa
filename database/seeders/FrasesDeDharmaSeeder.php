<?php

namespace Database\Seeders;

use App\Models\FraseDeDharma;
use Illuminate\Database\Seeder;

class FrasesDeDharmaSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/data/frases_de_dharma.csv');

        if (!file_exists($csvPath)) {
            $this->command?->warn("CSV no encontrado en {$csvPath}");
            return;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $this->command?->warn('No se pudo abrir el CSV');
            return;
        }

        // Saltar header (y quitar BOM del primer campo si existe)
        $header = fgetcsv($handle);
        if ($header !== false && isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        $now = now();
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 3) continue;

            [$numero, $cita, $libro] = $data;

            $rows[] = [
                'numero' => (int) $numero,
                'cita_textual' => self::fixEncoding(trim($cita)),
                'libro' => self::fixEncoding(trim($libro)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        fclose($handle);

        FraseDeDharma::query()->truncate();
        FraseDeDharma::insert($rows);

        $this->command?->info('Insertadas ' . count($rows) . ' frases de Dharma.');
    }

    /**
     * Si el texto venía con doble-encoding UTF-8 (típico de CSVs mal exportados
     * desde Excel/Windows), lo normaliza. Si ya está bien, lo deja igual.
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
