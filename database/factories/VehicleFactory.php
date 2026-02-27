<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vin = null;

        if ($this->faker->boolean(70)) {
            $vin = strtoupper($this->faker->unique()->bothify('?????????????????'));
        }

        return [
            'customer_id' => Customer::factory(),
            'plate' => strtoupper($this->faker->unique()->bothify('???-####')),
            'vin' => $vin,
            'make' => $this->faker->randomElement([
                'Chevrolet',
                'Renault',
                'Mazda',
                'Toyota',
                'Nissan',
                'Kia',
            ]),
            'model' => $this->faker->randomElement([
                'Onix',
                'Duster',
                'CX-5',
                'Corolla',
                'Versa',
                'Sportage',
            ]),
            'year' => $this->faker->numberBetween(2000, now()->year + 1),
            'type' => $this->faker->randomElement([
                'sedan',
                'suv',
                'pickup',
                'van',
                'motorcycle',
                'truck',
            ]),
            'color' => $this->faker->safeColorName(),
            'fuel_type' => $this->faker->randomElement([
                'gasoline',
                'diesel',
                'electric',
                'hybrid',
                'gas',
            ]),
            'transmission' => $this->faker->randomElement([
                'manual',
                'automatic',
            ]),
            'mileage' => $this->faker->numberBetween(0, 250000),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
