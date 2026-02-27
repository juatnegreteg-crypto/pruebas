<?php

namespace App\Services\Iam;

use App\Models\Technician;
use App\Models\User;

class UserTechnicianLinkService
{
    public function __construct(private readonly UserPartySyncService $userPartySyncService) {}

    public function syncForUser(User $user): void
    {
        $user->loadMissing('profile');
        $party = $this->userPartySyncService->ensureForUser($user);

        $isTechnicianProfile = (bool) ($user->profile?->is_technician_profile ?? false);

        if (! $isTechnicianProfile || ! $user->is_active) {
            Technician::query()->where('party_id', $party->id)->delete();

            return;
        }

        Technician::query()->updateOrCreate(
            ['party_id' => $party->id],
            [
                'is_active' => true,
            ],
        );
    }
}
