<?php

namespace App\Http\Resources;

use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Technician $resource
 */
class TechnicianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $technician = $this->resource;
        $technician->loadMissing(['party.emails', 'party.phones', 'party.addresses']);

        $addresses = $technician->party?->addresses?->map(function ($address): array {
            return [
                'id' => $address->id,
                'type' => $address->type,
                'isPrimary' => (bool) $address->is_primary,
                'street' => $address->street,
                'complement' => $address->complement,
                'neighborhood' => $address->neighborhood,
                'city' => $address->city,
                'state' => $address->state,
                'postalCode' => $address->postal_code,
                'country' => $address->country,
                'reference' => $address->reference,
            ];
        })->values();

        return [
            'id' => $technician->id,
            'name' => $technician->party?->display_name ?? $technician->name,
            'email' => $technician->party?->emails?->firstWhere('is_primary', true)?->email,
            'phone' => $technician->party?->phones?->firstWhere('is_primary', true)?->phone_number,
            'isActive' => (bool) $technician->is_active,
            'hasAvailability' => (bool) ($technician->has_availability ?? false),
            'addresses' => $addresses ?? [],
        ];
    }
}
