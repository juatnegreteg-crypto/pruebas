<?php

namespace App\Http\Resources;

use App\Models\Bundle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Bundle $resource
 */
class BundleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $bundle = $this->resource;
        $bundle->loadMissing('catalogItem.taxRates.tax');
        $taxes = $bundle->catalogItem?->taxRates?->map(function ($taxRate): array {
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
        $observation = $bundle->catalogItem?->latestObservation('general', ['internal']);
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
            'id' => $bundle->id,
            'name' => $bundle->name,
            'description' => $bundle->description,
            'observations' => $observations,
            'cost' => $bundle->cost,
            'price' => $bundle->price,
            'currency' => $bundle->currency,
            'unit' => $bundle->unit?->value ?? $bundle->unit,
            'isActive' => $bundle->is_active,
            'itemsCount' => $bundle->bundleables_count ?? $bundle->items_count ?? 0,
            'items' => $this->whenItems(),
            'taxes' => $taxes ?? [],
        ];
    }

    public function whenItems(): AnonymousResourceCollection
    {
        return BundleItemResource::collection($this->whenLoaded('bundleables'));
    }
}
