<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Customer $resource
 */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $customer = $this->resource;
        $customer->loadMissing(['party.person', 'party.organization', 'party.emails', 'party.phones', 'party.addresses']);
        $party = $customer->party;
        $primaryEmail = $party?->emails?->firstWhere('is_primary', true);
        $primaryPhone = $party?->phones?->firstWhere('is_primary', true);
        $displayName = $party?->type === 'organization'
            ? ($party->organization?->legal_name ?? $party->display_name)
            : ($party?->person?->full_name ?? $party?->display_name);
        $addresses = $party?->addresses?->map(function ($address): array {
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
        $observation = $customer->latestObservation('general', ['internal']);
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
            'id' => $customer->id,
            'fullName' => $displayName ?? $customer->full_name,
            'email' => $primaryEmail?->email ?? $customer->email,
            'documentType' => $customer->document_type?->value,
            'documentNumber' => $customer->document_number,
            'phoneNumber' => $primaryPhone?->phone_number ?? $customer->phone_number,
            'observations' => $observations,
            'addresses' => $addresses ?? [],
            'createdAt' => $customer->created_at?->toIso8601String(),
        ];
    }
}
