<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Membresia;

class MembresiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Datos a poblar
        $membresias = [
            ['nombre' => 'TK Clases', 'descripcion' => 'Tarjeta Kadampa Clases', 'entidad_id' => '1'],
            ['nombre' => 'TK Corazón', 'descripcion' => 'Tarjeta Kadampa Corazón', 'entidad_id' => '1'],
            ['nombre' => 'TK Benefactor', 'descripcion' => 'Tarjeta Kadampa Benefactor', 'entidad_id' => '1'],
        ];

        // Iterar sobre los datos y crear registros en la tabla
        foreach ($membresias as $membresia) {
            Membresia::create($membresia);
        }
    }
}
