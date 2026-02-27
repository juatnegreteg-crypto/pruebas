<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\Iam\UserPartySyncService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private readonly UserPartySyncService $userPartySyncService) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $baseUsername = trim((string) ($input['name'] ?? ''));
        $baseUsername = $baseUsername !== '' ? $baseUsername : Str::before((string) $input['email'], '@');
        $base = $baseUsername !== '' ? $baseUsername : Str::lower(Str::random(12));
        $candidateUsername = $base;
        $suffix = 1;

        while (User::query()->where('username', $candidateUsername)->exists()) {
            $suffix++;
            $candidateUsername = "{$base}-{$suffix}";
        }

        $displayName = trim((string) ($input['name'] ?? '')) ?: $candidateUsername;
        $party = $this->userPartySyncService->createForUserData(
            $displayName,
            (string) $input['email'],
            true,
            false,
            null,
        );

        return User::create([
            'party_id' => $party->id,
            'name' => $candidateUsername,
            'username' => $candidateUsername,
            'full_name' => trim((string) ($input['name'] ?? '')) ?: null,
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
