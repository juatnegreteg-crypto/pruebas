<?php

namespace App\Observers;

use App\Enums\CatalogItemType;
use App\Models\CatalogItem;
use App\Models\Product;

class ProductObserver
{
    public function saving(Product $product): void
    {
        $common = $this->pullCommonAttributes($product);

        if (! $product->catalog_item_id) {
            $catalogItem = CatalogItem::query()->create(array_merge([
                'type' => CatalogItemType::PRODUCT,
            ], $common));

            $product->catalog_item_id = $catalogItem->id;

            return;
        }

        if ($common) {
            $product->catalogItem()->update($common);
        }
    }

    public function deleted(Product $product): void
    {
        $catalogItem = $product->catalogItem()->withTrashed()->first();

        if (! $catalogItem) {
            return;
        }

        if ($product->isForceDeleting()) {
            $catalogItem->forceDelete();

            return;
        }

        $catalogItem->delete();
    }

    public function restored(Product $product): void
    {
        $catalogItem = $product->catalogItem()->withTrashed()->first();

        if ($catalogItem && $catalogItem->trashed()) {
            $catalogItem->restore();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function pullCommonAttributes(Product $product): array
    {
        $keys = ['name', 'description', 'cost', 'price', 'currency', 'unit', 'is_active'];
        $attributes = $product->getAttributes();
        $common = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $attributes)) {
                $common[$key] = $attributes[$key];
                $product->offsetUnset($key);
            }
        }

        return $common;
    }
}
