<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 10000, 500000);
        $taxRate = 19; // IVA Colombia
        $taxTotal = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxTotal;

        return [
            'vehicle_id' => Vehicle::factory(),
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
        ];
    }

    /**
     * Indica que la cotizacion tiene totales en cero.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'subtotal' => 0,
            'tax_total' => 0,
            'total' => 0,
        ]);
    }
}
