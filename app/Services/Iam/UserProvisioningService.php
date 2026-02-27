<?php

namespace App\Services\Iam;

use App\Models\Party;
use App\Models\User;
use App\Notifications\UserTemporaryPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserProvisioningService
{
    public function __construct(private readonly UserPartySyncService $userPartySyncService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function provision(array $data): User
    {
        $username = $this->resolveUniqueUsername($this->resolveUsernameBase($data));
        $temporaryPassword = Str::password(16, true, true, true, false);

        /** @var User $user */
        $user = DB::transaction(function () use ($data, $username, $temporaryPassword): User {
            $party = $this->resolveParty($data, $username);
            $fullName = trim((string) ($data['full_name'] ?? ''));

            return User::query()->create([
                'party_id' => $party->id,
                'name' => $username,
                'username' => $username,
                'full_name' => $fullName !== '' ? $fullName : null,
                'email' => $data['email'],
                'password' => $temporaryPassword,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });

        $user->notify(new UserTemporaryPasswordNotification($username, $temporaryPassword));

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveParty(array $data, string $username): Party
    {
        $partyId = $data['party_id'] ?? null;

        if ($partyId !== null) {
            $party = Party::query()
                ->whereKey((int) $partyId)
                ->whereDoesntHave('user')
                ->first();

            if ($party === null) {
                throw ValidationException::withMessages([
                    'party_id' => 'La entidad seleccionada ya no está disponible.',
                ]);
            }

            return $party;
        }

        $displayName = trim((string) ($data['full_name'] ?? '')) ?: $username;

        return $this->userPartySyncService->createForUserData(
            $displayName,
            (string) $data['email'],
            (bool) ($data['is_active'] ?? true),
            false,
            null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveUsernameBase(array $data): string
    {
        $provided = trim((string) ($data['username'] ?? ''));
        if ($provided !== '') {
            return $this->normalizeUsername($provided);
        }

        $fullName = trim((string) ($data['full_name'] ?? ''));
        if ($fullName !== '') {
            return $this->normalizeUsername($fullName);
        }

        $email = trim((string) ($data['email'] ?? ''));
        $emailLocalPart = Str::before(Str::lower($email), '@');
        if ($emailLocalPart !== '') {
            return $this->normalizeUsername($emailLocalPart);
        }

        return Str::lower(Str::random(12));
    }

    private function normalizeUsername(string $value): string
    {
        $normalized = Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();

        return $normalized !== '' ? $normalized : Str::lower(Str::random(12));
    }

    private function resolveUniqueUsername(string $baseUsername): string
    {
        $normalized = trim($baseUsername);
        $base = $normalized !== '' ? $normalized : Str::lower(Str::random(12));
        $candidateUsername = $base;
        $suffix = 1;

        while (User::query()->where('username', $candidateUsername)->exists()) {
            $suffix++;
            $candidateUsername = "{$base}-{$suffix}";
        }

        return $candidateUsername;
    }
}
