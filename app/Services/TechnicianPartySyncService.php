<?php

namespace App\Services;

use App\Models\Party;
use App\Models\PartyEmail;
use App\Models\PartyPerson;
use App\Models\PartyPhone;
use App\Models\Technician;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TechnicianPartySyncService
{
    public function __construct(private readonly ?PartyAddressSyncService $partyAddressSyncService = null) {}

    public function create(array $data): Technician
    {
        return DB::transaction(function () use ($data): Technician {
            $party = Party::query()->create([
                'type' => 'person',
                'display_name' => (string) $data['name'],
                'is_active' => (bool) ($data['isActive'] ?? true),
            ]);

            PartyPerson::query()->create([
                'party_id' => $party->id,
                'full_name' => (string) $data['name'],
            ]);

            $this->syncPrimaryEmail($party, $data['email'] ?? null);
            $this->syncPrimaryPhone($party, $data['phone'] ?? null);
            $this->syncAddresses($party, $data['addresses'] ?? null);

            return Technician::query()->create([
                'party_id' => $party->id,
                'is_active' => (bool) ($data['isActive'] ?? true),
            ]);
        });
    }

    public function update(Technician $technician, array $data): Technician
    {
        return DB::transaction(function () use ($technician, $data): Technician {
            $technician->loadMissing('party.person');

            if ($technician->party === null) {
                $party = Party::query()->create([
                    'type' => 'person',
                    'display_name' => (string) ($data['name'] ?? $technician->name),
                    'is_active' => (bool) ($data['isActive'] ?? $technician->is_active),
                ]);

                $technician->party()->associate($party);
                $technician->save();
                $technician->setRelation('party', $party);
            }

            $name = (string) ($data['name'] ?? $technician->name);
            $email = $data['email'] ?? $technician->email;
            $phone = $data['phone'] ?? $technician->phone;
            $isActive = (bool) ($data['isActive'] ?? $technician->is_active);

            $technician->party->update([
                'display_name' => $name,
                'is_active' => $isActive,
            ]);

            if ($technician->party->person === null) {
                PartyPerson::query()->create([
                    'party_id' => $technician->party->id,
                    'full_name' => $name,
                ]);
            } else {
                $technician->party->person->update([
                    'full_name' => $name,
                ]);
            }

            $this->syncPrimaryEmail($technician->party, $email);
            $this->syncPrimaryPhone($technician->party, $phone);
            $this->syncAddresses($technician->party, $data['addresses'] ?? null);

            $technician->update([
                'is_active' => $isActive,
            ]);

            return $technician->refresh();
        });
    }

    private function syncPrimaryEmail(Party $party, ?string $email): void
    {
        $normalizedEmail = $email !== null && trim($email) !== '' ? Str::lower(trim($email)) : null;
        $primaryEmail = PartyEmail::query()->withTrashed()
            ->where('party_id', $party->id)
            ->where('is_primary', true)
            ->first();

        if ($normalizedEmail === null) {
            if ($primaryEmail !== null) {
                $primaryEmail->delete();
            }

            return;
        }

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
