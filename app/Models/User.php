<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'username',
        'full_name',
        'email',
        'password',
        'is_active',
        'party_id',
        'profile_id',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
            'party_id' => 'integer',
            'profile_id' => 'integer',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_user')->withTimestamps();
    }

    public function hasPermissionToManage(string $permission): bool
    {
        return $this->is_active && $this->can($permission);
    }

    public function syncSingleProfile(?Profile $profile): void
    {
        if ($profile === null) {
            $this->profile()->associate(null);
            $this->save();
            $this->syncRoles([]);

            return;
        }

        $this->profile()->associate($profile);
        $this->save();
        $this->syncRoles([$profile]);
    }

    public function effectiveSkills()
    {
        $profileSkills = $this->profile?->skills()->where('skills.is_active', true)->get() ?? collect();
        $directSkills = $this->skills()->where('skills.is_active', true)->get();

        return $profileSkills
            ->concat($directSkills)
            ->unique('id')
            ->values();
    }
}
