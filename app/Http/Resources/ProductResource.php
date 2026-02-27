<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Product $resource
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->resource;
        $product->loadMissing('catalogItem.taxRates.tax');
        $taxes = $product->catalogItem?->taxRates?->map(function ($taxRate): array {
            return [
                'taxId' => $taxRate->tax_id,
                'name' => $taxRate->tax?->name,
                'code' => $taxRate->tax?->code,
                'jurisdiction' => $taxRate->tax?->jurisdiction,
                'rate' => $taxRate->rate,
                'startAt' => $taxRate->start_date?->toDateString(),
                'endAt' => $taxRate->end_date?->toDateString(),
            ];
        })->values();
        $observation = $product->catalogItem?->latestObservation('general', ['internal']);
        $observations = $observation
            ? [[
                'body' => $observation->body,
                'context' => 'general',
                'audienceTags' => $observation->audience_tags ?? [],
                'createdAt' => $observation->created_at?->toIso8601String(),
                'createdBy' => $observation->created_by,
            ]]
            : [];

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'observations' => $observations,
            'cost' => $product->cost,
            'price' => $product->price,
            'currency' => $product->currency,
            'unit' => $product->unit?->value ?? $product->unit,
            'isActive' => $product->is_active,
            'taxes' => $taxes ?? [],
        ];
    }
}
