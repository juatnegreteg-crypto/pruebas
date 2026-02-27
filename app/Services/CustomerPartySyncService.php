<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Party;
use App\Models\PartyEmail;
use App\Models\PartyOrganization;
use App\Models\PartyPerson;
use App\Models\PartyPhone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerPartySyncService
{
    public function __construct(private readonly ?PartyAddressSyncService $partyAddressSyncService = null) {}

    public function create(array $data, bool $useTransaction = true): Customer
    {
        $callback = function () use ($data): Customer {
            $isOrganization = $this->isOrganization((string) $data['documentType']);
            $displayName = (string) $data['fullName'];

            $party = Party::query()->create([
                'type' => $isOrganization ? 'organization' : 'person',
                'display_name' => $displayName,
                'is_active' => true,
            ]);

            $this->syncPartyProfile(
                party: $party,
                isOrganization: $isOrganization,
                displayName: $displayName,
                taxId: $isOrganization ? (string) $data['documentNumber'] : null,
            );
            $this->syncPrimaryEmail($party, (string) $data['email']);
            $this->syncPrimaryPhone($party, $data['phoneNumber'] ?? null);
            $this->syncAddresses($party, $data['addresses'] ?? null);

            return Customer::query()->create([
                'party_id' => $party->id,
                'full_name' => $displayName,
                'email' => (string) $data['email'],
                'document_type' => (string) $data['documentType'],
                'document_number' => (string) $data['documentNumber'],
                'phone_number' => $data['phoneNumber'] ?? null,
            ]);
        };

        if ($useTransaction) {
            return DB::transaction($callback);
        }

        return $callback();
    }

    public function update(Customer $customer, array $data, bool $useTransaction = true): Customer
    {
        $callback = function () use ($customer, $data): Customer {
            $customer->loadMissing('party');

            if ($customer->party === null) {
                $isOrganization = $this->isOrganization((string) $data['documentType']);
                $displayName = (string) $data['fullName'];

                $party = Party::query()->create([
                    'type' => $isOrganization ? 'organization' : 'person',
                    'display_name' => $displayName,
                    'is_active' => true,
                ]);

                $customer->party()->associate($party);
                $customer->save();
                $customer->setRelation('party', $party);
            }

            $documentType = (string) ($data['documentType'] ?? $customer->document_type?->value ?? 'CC');
            $isOrganization = $this->isOrganization($documentType);
            $displayName = (string) ($data['fullName'] ?? $customer->full_name);
            $documentNumber = (string) ($data['documentNumber'] ?? $customer->document_number);

            $customer->party->update([
                'type' => $isOrganization ? 'organization' : 'person',
                'display_name' => $displayName,
            ]);

            $this->syncPartyProfile(
                party: $customer->party,
                isOrganization: $isOrganization,
                displayName: $displayName,
                taxId: $isOrganization ? $documentNumber : null,
            );
            $this->syncPrimaryEmail($customer->party, (string) ($data['email'] ?? $customer->email));
            $this->syncPrimaryPhone($customer->party, $data['phoneNumber'] ?? $customer->phone_number);
            $this->syncAddresses($customer->party, $data['addresses'] ?? null);

            $customer->update([
                'full_name' => $displayName,
                'email' => (string) ($data['email'] ?? $customer->email),
                'document_type' => $documentType,
                'document_number' => $documentNumber,
                'phone_number' => $data['phoneNumber'] ?? $customer->phone_number,
            ]);

            return $customer->refresh();
        };

        if ($useTransaction) {
            return DB::transaction($callback);
        }

        return $callback();
    }

    private function isOrganization(string $documentType): bool
    {
        return Str::upper($documentType) === 'NIT';
    }

    private function syncPartyProfile(Party $party, bool $isOrganization, string $displayName, ?string $taxId): void
    {
        if ($isOrganization) {
            PartyPerson::query()->where('party_id', $party->id)->delete();

            $organization = PartyOrganization::query()->where('party_id', $party->id)->first();

            if ($organization === null) {
                PartyOrganization::query()->create([
                    'party_id' => $party->id,
                    'legal_name' => $displayName,
                    'trade_name' => null,
                    'tax_id' => $taxId,
                ]);

                return;
            }

            $organization->update([
                'legal_name' => $displayName,
                'tax_id' => $taxId,
            ]);

            return;
        }

        PartyOrganization::query()->where('party_id', $party->id)->delete();
        $person = PartyPerson::query()->where('party_id', $party->id)->first();

        if ($person === null) {
            PartyPerson::query()->create([
                'party_id' => $party->id,
                'full_name' => $displayName,
            ]);

            return;
        }

        $person->update([
            'full_name' => $displayName,
        ]);
    }

    private function syncPrimaryEmail(Party $party, string $email): void
    {
        $normalizedEmail = Str::lower(trim($email));
        $primaryEmail = PartyEmail::query()->withTrashed()
            ->where('party_id', $party->id)
            ->where('is_primary', true)
            ->first();

        if ($primaryEmail === null) {
            PartyEmail::query()->create([
                'party_id' => $party->id,
                'email' => $normalizedEmail,
                'type' => 'primary',
                'is_primary' => true,
                'is_verified' => false,
            ]);

            return;
        }

        if ($primaryEmail->trashed()) {
            $primaryEmail->restore();
        }

        $primaryEmail->update([
            'email' => $normalizedEmail,
            'is_primary' => true,
        ]);
    }

    private function syncPrimaryPhone(Party $party, ?string $phoneNumber): void
    {
        $normalizedPhone = $phoneNumber !== null && trim($phoneNumber) !== '' ? trim($phoneNumber) : null;
        $primaryPhone = PartyPhone::query()->withTrashed()
            ->where('party_id', $party->id)
            ->where('is_primary', true)
            ->first();

        if ($normalizedPhone === null) {
            if ($primaryPhone !== null) {
                $primaryPhone->delete();
            }

            return;
        }

        if ($primaryPhone === null) {
            PartyPhone::query()->create([
                'party_id' => $party->id,
                'phone_number' => $normalizedPhone,
                'type' => 'primary',
                'is_primary' => true,
            ]);

            return;
        }

        if ($primaryPhone->trashed()) {
            $primaryPhone->restore();
        }

        $primaryPhone->update([
            'phone_number' => $normalizedPhone,
            'is_primary' => true,
        ]);
    }

    private function syncAddresses(Party $party, ?array $addresses): void
    {
        $service = $this->partyAddressSyncService ?? app(PartyAddressSyncService::class);
        $service->sync($party, $addresses);
    }
}
