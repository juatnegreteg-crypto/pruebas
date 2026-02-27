<?php

namespace Database\Seeders;

use App\Enums\QuoteStatus;
use App\Models\Bundle;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Service;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class QuoteSeeder extends Seeder
{
    private const MIN_QUOTES = 10;

    private const MIN_VEHICLES = 10;

    private const MIN_PRODUCTS = 10;

    private const MIN_SERVICES = 10;

    public function run(): void
    {
        $this->ensureSeedDependencies();

        $items = $this->availableItems();
        $vehicles = Vehicle::query()
            ->get(['id']);
        $statuses = collect(QuoteStatus::cases());

        $quotesToCreate = max(0, self::MIN_QUOTES - Quote::query()
            ->count());

        Quote::factory()
            ->count($quotesToCreate)
            ->empty()
            ->state(fn (): array => [
                'vehicle_id' => $vehicles->random()->id,
                'status' => $statuses->random(),
            ])
            ->create()
            ->each(function (Quote $quote) use ($items): void {
                $quote->items()
                    ->createMany($this->randomQuoteItemsPayload($items));
            });
    }

    /**
     * @return Collection<int, array{type: class-string, id: int, name: string, price: float}>
     */
    private function availableItems(): Collection
    {
        $products = $this->toSeedItems(Product::query()
            ->with('catalogItem')
            ->get(), Product::class)
            ->toArray();

        $services = $this->toSeedItems(Service::query()
            ->with('catalogItem')
            ->get(), Service::class)
            ->toArray();

        $bundles = $this->toSeedItems(Bundle::query()
            ->with('catalogItem')
            ->get(), Bundle::class)
            ->toArray();

        return collect()
            ->concat($products)
            ->concat($services)
            ->concat($bundles)
            ->values();
    }

    /**
     * @param  Collection<int, Product|Service|Bundle>  $items
     * @return Collection<int, array{type: class-string, id: int, name: string, price: float}>
     */
    private function toSeedItems(Collection $items, string $type): Collection
    {
        return $items->map(function (Product|Service|Bundle $itemable) use ($type): ?array {
            if (! $itemable->id) {
                return null;
            }

            if (! $itemable->name) {
                return null;
            }

            if ($itemable->price === null) {
                return null;
            }

            return [
                'type' => $type,
                'id' => (int) $itemable->id,
                'name' => (string) $itemable->name,
                'price' => (float) $itemable->price,
            ];
        })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, array{type: class-string, id: int, name: string, price: float}>  $items
     * @return array<int, array{
     *     itemable_type: class-string,
     *     itemable_id: int,
     *     description: string,
     *     quantity: int,
     *     unit_price: float,
     *     tax_rate: int
     * }>
     */
    private function randomQuoteItemsPayload(Collection $items): array
    {
        return collect(range(1, fake()->numberBetween(1, 5)))
            ->map(function () use ($items): array {
                $itemable = $items->random();

                return [
                    'itemable_type' => $itemable['type'],
                    'itemable_id' => $itemable['id'],
                    'description' => $itemable['name'],
                    'quantity' => fake()->numberBetween(1, 3),
                    'unit_price' => $itemable['price'],
                    'tax_rate' => 19,
                ];
            })
            ->all();
    }

    private function ensureSeedDependencies(): void
    {
        $vehiclesToCreate = max(0, self::MIN_VEHICLES - Vehicle::query()
            ->count());
        Vehicle::factory()
            ->count($vehiclesToCreate)
            ->create();

        $productsToCreate = max(0, self::MIN_PRODUCTS - Product::query()
            ->count());
        Product::factory()
            ->count($productsToCreate)
            ->create();

        $servicesToCreate = max(0, self::MIN_SERVICES - Service::query()
            ->count());
        Service::factory()
            ->count($servicesToCreate)
            ->create();
    }
}
