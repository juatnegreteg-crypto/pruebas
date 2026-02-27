<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Service $resource
 */
class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = $this->resource;
        $service->loadMissing('catalogItem.taxRates.tax');
        $taxes = $service->catalogItem?->taxRates?->map(function ($taxRate): array {
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
        $observation = $service->catalogItem?->latestObservation('general', ['internal']);
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
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'observations' => $observations,
            'cost' => $service->cost,
            'price' => $service->price,
            'currency' => $service->currency,
            'unit' => $service->unit?->value ?? $service->unit,
            'isActive' => $service->is_active,
            'taxes' => $taxes ?? [],
        ];
    }
}
