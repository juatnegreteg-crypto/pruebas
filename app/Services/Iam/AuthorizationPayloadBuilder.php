<?php

namespace App\Services\Iam;

use App\Models\User;
use Illuminate\Support\Carbon;

class AuthorizationPayloadBuilder
{
    public function build(User $user): array
    {
        $user->loadMissing(['party', 'profile.permissions', 'profile.skills', 'skills']);

        $permissions = $user->getAllPermissions()
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();

        $skills = $user->effectiveSkills()
            ->map(fn ($skill): array => [
                'id' => $skill->id,
                'name' => $skill->name,
                'slug' => $skill->slug,
            ])
            ->sortBy('name')
            ->values();

        $versionSeed = [
            $user->updated_at?->toISOString(),
            $user->profile?->updated_at?->toISOString(),
            $permissions->implode('|'),
            $skills->pluck('slug')->implode('|'),
        ];

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name ?: $user->party?->display_name ?: $user->username ?: $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'isActive' => (bool) $user->is_active,
            ],
            'profile' => $user->profile ? [
                'id' => $user->profile->id,
                'name' => $user->profile->name,
                'slug' => $user->profile->slug ?? $user->profile->name,
            ] : null,
            'permissions' => $permissions,
            'skills' => $skills,
            'version' => crc32(implode('|', $versionSeed)),
            'generatedAt' => Carbon::now()->toISOString(),
        ];
    }
}
