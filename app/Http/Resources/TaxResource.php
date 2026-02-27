<?php

namespace App\Http\Resources;

use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Tax $resource
 */
class TaxResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tax = $this->resource;

        return [
            'id' => $tax->id,
            'name' => $tax->name,
            'code' => $tax->code,
            'jurisdiction' => $tax->jurisdiction,
            'rate' => $tax->rate !== null ? (float) $tax->rate : null,
        ];
    }
}
