<?php

namespace App\Services;

use App\Models\Party;
use App\Models\PartyAddress;
use Illuminate\Support\Arr;

class PartyAddressSyncService
{
    /**
     * @param  array<int, array<string, mixed>>|null  $addresses
     */
    public function sync(Party $party, ?array $addresses): void
    {
        if ($addresses === null) {
            return;
        }

        $existing = $party->addresses()->get()->keyBy('id');
        $incoming = collect($addresses)->map(fn (array $address) => $this->normalize($address));

        if ($incoming->isEmpty()) {
            $party->addresses()->delete();

            return;
        }

        $primaryIndex = $incoming->search(fn (array $address): bool => (bool) ($address['is_primary'] ?? false));

        $keptIds = [];
        foreach ($incoming as $index => $address) {
            if ($primaryIndex !== null && $primaryIndex !== false) {
                $address['is_primary'] = $index === $primaryIndex;
            }

            $addressId = Arr::get($address, 'id');
            if ($addressId && $existing->has($addressId)) {
                /** @var PartyAddress $record */
                $record = $existing->get($addressId);
                $record->update($address);
                $keptIds[] = $record->id;

                continue;
            }

            $created = $party->addresses()->create($address);
            $keptIds[] = $created->id;
        }

        $party->addresses()
            ->whereNotIn('id', $keptIds)
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>
     */
    private function normalize(array $address): array
    {
        return [
            'id' => Arr::get($address, 'id'),
            'type' => (string) Arr::get($address, 'type', 'primary'),
            'is_primary' => (bool) Arr::get($address, 'isPrimary', false),
            'street' => trim((string) Arr::get($address, 'street', '')),
            'complement' => $this->trimNullable(Arr::get($address, 'complement')),
            'neighborhood' => $this->trimNullable(Arr::get($address, 'neighborhood')),
            'city' => trim((string) Arr::get($address, 'city', '')),
            'state' => trim((string) Arr::get($address, 'state', '')),
            'postal_code' => $this->trimNullable(Arr::get($address, 'postalCode')),
            'country' => trim((string) Arr::get($address, 'country', '')),
            'reference' => $this->trimNullable(Arr::get($address, 'reference')),
        ];
    }

    private function trimNullable(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
