<?php

namespace App\Observers;

use App\Enums\CatalogItemType;
use App\Models\CatalogItem;
use App\Models\Service;

class ServiceObserver
{
    public function saving(Service $service): void
    {
        $common = $this->pullCommonAttributes($service);

        if (! $service->catalog_item_id) {
            $catalogItem = CatalogItem::query()->create(array_merge([
                'type' => CatalogItemType::SERVICE,
            ], $common));

            $service->catalog_item_id = $catalogItem->id;

            return;
        }

        if ($common) {
            $service->catalogItem()->update($common);
        }
    }

    public function deleted(Service $service): void
    {
        $catalogItem = $service->catalogItem()->withTrashed()->first();

        if (! $catalogItem) {
            return;
        }

        if ($service->isForceDeleting()) {
            $catalogItem->forceDelete();

            return;
        }

        $catalogItem->delete();
    }

    public function restored(Service $service): void
    {
        $catalogItem = $service->catalogItem()->withTrashed()->first();

        if ($catalogItem && $catalogItem->trashed()) {
            $catalogItem->restore();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function pullCommonAttributes(Service $service): array
    {
        $keys = ['name', 'description', 'cost', 'price', 'currency', 'unit', 'is_active'];
        $attributes = $service->getAttributes();
        $common = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $attributes)) {
                $common[$key] = $attributes[$key];
                $service->offsetUnset($key);
            }
        }

        return $common;
    }
}
