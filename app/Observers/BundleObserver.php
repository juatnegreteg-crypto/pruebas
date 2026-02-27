<?php

namespace App\Observers;

use App\Enums\CatalogItemType;
use App\Models\Bundle;
use App\Models\CatalogItem;

class BundleObserver
{
    public function saving(Bundle $bundle): void
    {
        $common = $this->pullCommonAttributes($bundle);

        if (! $bundle->catalog_item_id) {
            $catalogItem = CatalogItem::query()->create(array_merge([
                'type' => CatalogItemType::BUNDLE,
            ], $common));

            $bundle->catalog_item_id = $catalogItem->id;

            return;
        }

        if ($common) {
            $bundle->catalogItem()->update($common);
        }
    }

    public function deleted(Bundle $bundle): void
    {
        $catalogItem = $bundle->catalogItem()->withTrashed()->first();

        if (! $catalogItem) {
            return;
        }

        if ($bundle->isForceDeleting()) {
            $catalogItem->forceDelete();

            return;
        }

        $catalogItem->delete();
    }

    public function restored(Bundle $bundle): void
    {
        $catalogItem = $bundle->catalogItem()->withTrashed()->first();

        if ($catalogItem && $catalogItem->trashed()) {
            $catalogItem->restore();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function pullCommonAttributes(Bundle $bundle): array
    {
        $keys = ['name', 'description', 'cost', 'price', 'currency', 'unit', 'is_active'];
        $attributes = $bundle->getAttributes();
        $common = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $attributes)) {
                $common[$key] = $attributes[$key];
                $bundle->offsetUnset($key);
            }
        }

        return $common;
    }
}
