<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User $resource
 */
class UserOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $user->loadMissing('party');

        return [
            'id' => $user->id,
            'name' => $user->full_name ?: $user->party?->display_name ?: $user->username ?: $user->name,
        ];
    }
}
