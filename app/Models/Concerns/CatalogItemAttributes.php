<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait CatalogItemAttributes
{
    protected function id(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->catalog_item_id,
            set: fn ($value) => ['catalog_item_id' => $value]
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? $this->catalogItem?->name,
            set: fn ($value) => ['name' => $value]
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? $this->catalogItem?->description,
            set: fn ($value) => ['description' => $value]
        );
    }

    protected function cost(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? $this->catalogItem?->cost,
            set: fn ($value) => ['cost' => $value]
        );
    }

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? $this->catalogItem?->price,
            set: fn ($value) => ['price' => $value]
        );
    }

    protected function currency(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? $this->catalogItem?->currency,
            set: fn ($value) => ['currency' => $value]
        );
    }

    protected function unit(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? $this->catalogItem?->unit,
            set: fn ($value) => ['unit' => $value]
        );
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? $this->catalogItem?->is_active,
            set: fn ($value) => ['is_active' => $value]
        );
    }
}
