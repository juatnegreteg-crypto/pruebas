<?php

namespace App\Services\Iam;

use App\Models\Party;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Str;

class UserPartySyncService
{
    public function createForUserData(
        string $displayName,
        string $email,
        bool $isActive,
        bool $isEmailVerified = false,
        ?DateTimeInterface $verifiedAt = null,
    ): Party {
        $party = Party::query()->create([
            'type' => 'person',
            'display_name' => $displayName,
            'is_active' => $isActive,
        ]);

        $party->person()->create([
            'full_name' => $displayName,
        ]);

        $party->emails()->create([
            'email' => Str::lower($email),
            'type' => 'primary',
            'is_primary' => true,
            'is_verified' => $isEmailVerified,
            'verified_at' => $verifiedAt,
        ]);

        return $party;
    }

    public function ensureForUser(User $user): Party
    {
        $user->loadMissing(['party.person', 'party.emails']);

        if ($user->party === null) {
            $displayName = $this->resolveDisplayName($user);

            $party = $this->createForUserData(
                $displayName,
                $user->email,
                (bool) $user->is_active,
                $user->email_verified_at !== null,
                $user->email_verified_at,
            );

            $user->party()->associate($party);
            $user->save();

            return $party;
        }

        $this->syncFromUser($user);

        return $user->party;
    }

    public function syncFromUser(User $user): void
    {
        $user->loadMissing(['party.person', 'party.emails']);

        if ($user->party === null) {
            return;
        }

        $displayName = $this->resolveDisplayName($user);

        $user->party->update([
            'display_name' => $displayName,
            'is_active' => (bool) $user->is_active,
        ]);

        $person = $user->party->person;

        if ($person !== null) {
            $person->update([
                'full_name' => $displayName,
            ]);
        } else {
            $user->party->person()->create([
                'full_name' => $displayName,
            ]);
        }

        $primaryEmail = $user->party->emails()->where('is_primary', true)->first();

        if ($primaryEmail !== null) {
            $primaryEmail->update([
                'email' => Str::lower($user->email),
                'is_verified' => $user->email_verified_at !== null,
                'verified_at' => $user->email_verified_at,
            ]);
        } else {
            $user->party->emails()->create([
                'email' => Str::lower($user->email),
                'type' => 'primary',
                'is_primary' => true,
                'is_verified' => $user->email_verified_at !== null,
                'verified_at' => $user->email_verified_at,
            ]);
        }
    }

    private function resolveDisplayName(User $user): string
    {
        $fullName = trim((string) ($user->full_name ?? ''));
        $name = trim((string) ($user->name ?? ''));

        if ($fullName !== '') {
            return $fullName;
        }

        if ($name !== '') {
            return $name;
        }

        return $user->username ?: "user-{$user->id}";
    }
}
