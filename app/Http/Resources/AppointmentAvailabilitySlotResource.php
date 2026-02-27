<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{start: string, end: string, isAvailable: bool} $resource
 */
class AppointmentAvailabilitySlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var array{start: string, end: string, isAvailable: bool} $slot */
        $slot = $this->resource;

        return [
            'start' => $slot['start'],
            'end' => $slot['end'],
            'isAvailable' => (bool) $slot['isAvailable'],
        ];
    }
}
