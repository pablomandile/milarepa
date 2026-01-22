<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Barrios con provincia_id = 24 ===\n";
$barrios = DB::table('barrios')->where('provincia_id', 24)->get();
echo "Total: " . count($barrios) . "\n";

if (count($barrios) > 0) {
    echo "\nPrimeros 10:\n";
    foreach (array_slice($barrios, 0, 10) as $b) {
        echo "  ID: {$b->id}, Nombre: {$b->nombre}, Provincia ID: {$b->provincia_id}\n";
    }
}
