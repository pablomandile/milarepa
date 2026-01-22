<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Verificando tabla barrios ===\n";

if (Schema::hasTable('barrios')) {
    echo "Tabla 'barrios' existe\n";
    
    $columns = Schema::getColumnListing('barrios');
    echo "Columnas: " . implode(', ', $columns) . "\n";
    
    if (Schema::hasColumn('barrios', 'provincia_id')) {
        echo "La columna 'provincia_id' ya existe\n";
    } else {
        echo "La columna 'provincia_id' NO existe\n";
    }
    
    $count = DB::table('barrios')->count();
    echo "Total de barrios: " . $count . "\n";
} else {
    echo "Tabla 'barrios' NO existe\n";
}
