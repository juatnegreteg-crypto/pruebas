<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Diagnóstico eléctrico',
                'description' => 'Revisión de circuitos, batería y sistema de carga.',
                'price' => 78000.00,
                'currency' => 'COP',
                'is_active' => true,
            ],
            [
                'name' => 'Alineación y balanceo',
                'description' => 'Ajuste de dirección y balanceo de ruedas.',
                'price' => 62000.00,
                'currency' => 'COP',
                'is_active' => true,
            ],
            [
                'name' => 'Lavado premium',
                'description' => 'Lavado exterior, interior y encerado completo.',
                'price' => 45000.00,
                'currency' => 'COP',
                'is_active' => true,
            ],
            [
                'name' => 'Chequeo precompra',
                'description' => 'Evaluación integral del estado mecánico del vehículo.',
                'price' => 120000.00,
                'currency' => 'COP',
                'is_active' => true,
            ],
            [
                'name' => 'Servicio nocturno',
                'description' => 'Atención fuera de horario habitual bajo reserva.',
                'price' => 90000.00,
                'currency' => 'COP',
                'is_active' => false,
            ],
        ];

        foreach ($services as $service) {
            Service::query()->create($service);
        }
    }
}
