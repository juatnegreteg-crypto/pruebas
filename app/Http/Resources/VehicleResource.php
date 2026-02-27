<?php

namespace App\Http\Resources;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/**
 * @property-read Vehicle $resource
 */
class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $vehicle = $this->resource;
        $observation = $vehicle->latestObservation('general', ['internal']);
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
            'id' => $vehicle->id,
            'customerId' => $vehicle->customer_id,
            'customer' => $this->whenCustomer(),
            'plate' => $vehicle->plate,
            'vin' => $vehicle->vin,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'type' => $vehicle->type,
            'color' => $vehicle->color,
            'fuelType' => $vehicle->fuel_type,
            'transmission' => $vehicle->transmission,
            'mileage' => $vehicle->mileage,
            'isActive' => $vehicle->is_active,
            'observations' => $observations,
        ];
    }

    /**
     * @return MissingValue|CustomerResource
     */
    public function whenCustomer(): mixed
    {
        return $this->whenLoaded('customer', fn () => CustomerResource::make($this->resource->customer));
    }
}
