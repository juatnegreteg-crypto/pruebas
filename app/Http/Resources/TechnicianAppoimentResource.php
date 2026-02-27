<?php

namespace App\Http\Resources;

use App\Models\TechnicianAppoiment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read TechnicianAppoiment $resource
 */
class TechnicianAppoimentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $appointment = $this->resource;

        return [
            'id' => $appointment->id,
            'startsAt' => $appointment->starts_at?->toIso8601String(),
            'endsAt' => $appointment->ends_at?->toIso8601String(),
            'technicianId' => $appointment->technician_id,
            'status' => $appointment->status?->value,
            'observations' => $appointment->observations()
                ->orderByDesc('observations.created_at')
                ->get()
                ->map(fn ($observation): array => [
                    'body' => $observation->body,
                    'context' => $observation->pivot?->context,
                    'audienceTags' => $observation->audience_tags ?? [],
                    'createdAt' => $observation->created_at?->toIso8601String(),
                    'createdBy' => $observation->created_by,
                ])
                ->values(),
            'createdAt' => $appointment->created_at?->toIso8601String(),
            'updatedAt' => $appointment->updated_at?->toIso8601String(),
        ];
    }
}
