<?php

namespace App\Http\Resources;

use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Technician $resource
 */
class TechnicianOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Technician $technician */
        $technician = $this->resource;
        $technician->loadMissing('party');

        return [
            'id' => $technician->id,
            'name' => $technician->party?->display_name ?? $technician->name,
        ];
    }
}
