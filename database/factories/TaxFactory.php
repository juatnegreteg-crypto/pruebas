<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tax>
 */
class TaxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'code' => strtoupper($this->faker->unique()->bothify('TAX-###')),
            'jurisdiction' => $this->faker->randomElement(['Colombia', 'Antioquia', 'Bogotá']),
            'rate' => $this->faker->randomFloat(4, 0, 25),
        ];
    }
}
