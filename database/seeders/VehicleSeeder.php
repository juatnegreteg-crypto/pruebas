<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerIds = Customer::query()->pluck('id');
        if ($customerIds->isEmpty()) {
            $customerIds = Customer::factory()->count(1)->create()->pluck('id');
        }

        $vehicles = [
            [
                'plate' => 'ABC-123',
                'make' => 'Renault',
                'model' => 'Duster',
                'year' => 2022,
                'type' => 'suv',
                'color' => 'Gris',
                'fuel_type' => 'gasoline',
                'transmission' => 'manual',
                'mileage' => 45200,
                'is_active' => true,
            ],
            [
                'plate' => 'DEF-456',
                'make' => 'Chevrolet',
                'model' => 'Onix',
                'year' => 2021,
                'type' => 'sedan',
                'color' => 'Azul',
                'fuel_type' => 'gasoline',
                'transmission' => 'automatic',
                'mileage' => 37850,
                'is_active' => true,
            ],
            [
                'plate' => 'GHI-789',
                'make' => 'Kia',
                'model' => 'Sportage',
                'year' => 2020,
                'type' => 'suv',
                'color' => 'Negro',
                'fuel_type' => 'diesel',
                'transmission' => 'automatic',
                'mileage' => 68400,
                'is_active' => false,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            $vehicle['customer_id'] = $customerIds->random();

            $seededVehicle = Vehicle::withTrashed()->updateOrCreate(
                ['plate' => $vehicle['plate']],
                $vehicle,
            );

            if ($seededVehicle->trashed()) {
                $seededVehicle->restore();
            }
        }
    }
}
