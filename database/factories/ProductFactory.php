<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(10),
            'cost' => $this->faker->randomFloat(4, 1, 80000),
            'price' => $this->faker->randomFloat(4, 1, 100000),
            'currency' => 'COP',
            'is_active' => true,
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-#####')),
            'stock' => $this->faker->numberBetween(0, 500),
        ];
    }
}
