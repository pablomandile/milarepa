<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Moneda;

class MonedaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Datos a poblar
        $monedas = [
            ['nombre' => 'Pesos', 'simbolo' => '$'],
            ['nombre' => 'Dolares', 'simbolo' => 'USD'],
        ];

        // Iterar sobre los datos y crear registros en la tabla
        foreach ($monedas as $moneda) {
            Moneda::create($moneda);
        }
    }
}
