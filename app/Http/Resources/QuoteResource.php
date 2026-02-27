<?php

namespace App\Http\Resources;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Quote $resource
 */
class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $quote = $this->resource;

        return [
            'id' => $quote->id,
            'vehicleId' => $quote->vehicle_id,
            'vehicle' => $this->vehicle(),
            'customer' => $this->customer(),
            'status' => $quote->status?->value,
            'subtotal' => $quote->subtotal,
            'taxTotal' => $quote->tax_total,
            'total' => $quote->total,
            'itemsCount' => $this->itemsCount(),
            'items' => $this->items(),
            'createdAt' => $this->createdAt(),
            'updatedAt' => $this->updatedAt(),
        ];
    }

    private function vehicle(): mixed
    {
        return $this->whenLoaded('vehicle', fn () => VehicleResource::make($this->resource->vehicle));
    }

    private function customer(): mixed
    {
        return $this->whenLoaded('vehicle', fn () => CustomerResource::make($this->resource->vehicle->customer));
    }

    private function itemsCount(): mixed
    {
        return $this->whenCounted('items');
    }

    private function items(): AnonymousResourceCollection
    {
        return QuoteItemResource::collection($this->whenLoaded('items'));
    }

    private function createdAt(): ?string
    {
        return $this->resource->created_at?->toIso8601String();
    }

    private function updatedAt(): ?string
    {
        return $this->resource->updated_at?->toIso8601String();
    }
}
