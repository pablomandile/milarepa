<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EstadoTicket;

class EstadoticketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Datos a poblar
        $tickets = [
            ['estado' => 'No asignado'],
            ['estado' => 'Pendiente'],
            ['estado' => 'Resuelto'],
        ];

        // Iterar sobre los datos y crear registros en la tabla
        foreach ($tickets as $ticket) {
            EstadoTicket::create($ticket);
        }
    }
}
