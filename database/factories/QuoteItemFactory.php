<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteItemFactory extends Factory
{
    protected $model = QuoteItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 1000, 50000);
        $taxRate = 19; // IVA Colombia
        $subtotal = $quantity * $unitPrice;
        $taxTotal = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxTotal;

        return [
            'quote_id' => Quote::factory(),
            'itemable_type' => Product::class,
            'itemable_id' => Product::factory(),
            'description' => $this->faker->sentence(5),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
        ];
    }

    /**
     * Asocia el item a un producto especifico.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'itemable_type' => Product::class,
            'itemable_id' => $product->id,
            'description' => $product->name,
            'unit_price' => $product->price,
        ]);
    }

    /**
     * Asocia el item a una cotizacion especifica.
     */
    public function forQuote(Quote $quote): static
    {
        return $this->state(fn (array $attributes) => [
            'quote_id' => $quote->id,
        ]);
    }
}
