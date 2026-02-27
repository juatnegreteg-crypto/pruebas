<?php

namespace App\Http\Resources;

use App\Models\BundleItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read BundleItem $resource
 */
class BundleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $morph = $this->resource;

        $bundleable = $morph->bundleable;

        return [
            'id' => $morph->bundleable_id,
            'type' => $morph->bundleable_type,
            'name' => $bundleable->name,
            'description' => $bundleable->description,
            'price' => $bundleable->price,
            'currency' => $bundleable->currency,
            'isActive' => $bundleable->is_active,
            'quantity' => $morph->quantity,
        ];
    }
}
