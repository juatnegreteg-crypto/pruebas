<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(10),
            'cost' => $this->faker->randomFloat(4, 1, 80000),
            'price' => $this->faker->randomFloat(4, 1, 100000),
            'currency' => 'COP',
            'is_active' => true,
            'duration' => $this->faker->numberBetween(10, 240),
        ];
    }
}
