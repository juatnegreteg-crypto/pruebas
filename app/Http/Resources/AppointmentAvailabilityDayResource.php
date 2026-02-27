<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{date: string, slots: list<array{start: string, end: string, isAvailable: bool}>} $resource
 */
class AppointmentAvailabilityDayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var array{date: string, slots: list<array{start: string, end: string, isAvailable: bool}>} $day */
        $day = $this->resource;

        return [
            'date' => $day['date'],
            'slots' => AppointmentAvailabilitySlotResource::collection($day['slots']),
        ];
    }
}
