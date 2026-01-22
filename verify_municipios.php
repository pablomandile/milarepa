<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Municipio;

echo "=== Verificando Municipios ===\n";
$municipios = Municipio::all();
echo "Total de municipios: " . $municipios->count() . "\n";

if ($municipios->count() > 0) {
    echo "\nPrimeros 5 municipios:\n";
    foreach ($municipios->take(5) as $m) {
        echo "ID: {$m->id}, Nombre: {$m->nombre}, Provincia ID: {$m->provincia_id}\n";
    }
    
    echo "\nMunicipios con provincia_id = 1:\n";
    $mun1 = $municipios->filter(function($m) { return $m->provincia_id == 1; });
    echo "Total: " . $mun1->count() . "\n";
    foreach ($mun1 as $m) {
        echo "  ID: {$m->id}, Nombre: {$m->nombre}\n";
    }
} else {
    echo "La tabla municipios está vacía\n";
}
