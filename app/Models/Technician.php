<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Technician extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'party_id',
        'name',
        'email',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'name',
        'email',
        'phone',
    ];

    protected static function booted(): void
    {
        static::creating(function (Technician $technician): void {
            $identity = $technician->pullVirtualIdentity();

            if ($technician->party_id !== null) {
                return;
            }

            $displayName = trim((string) ($identity['name'] ?? ''));
            $displayName = $displayName !== '' ? $displayName : 'Técnico';

            $party = Party::query()->create([
                'type' => 'person',
                'display_name' => $displayName,
                'is_active' => (bool) $technician->is_active,
            ]);

            $party->person()->create([
                'full_name' => $displayName,
            ]);

            $email = trim((string) ($identity['email'] ?? ''));
            if ($email !== '') {
                $party->emails()->create([
                    'email' => Str::lower($email),
                    'type' => 'primary',
                    'is_primary' => true,
                    'is_verified' => false,
                ]);
            }

            $phone = trim((string) ($identity['phone'] ?? ''));
            if ($phone !== '') {
                $party->phones()->create([
                    'phone_number' => $phone,
                    'type' => 'primary',
                    'is_primary' => true,
                ]);
            }

            $technician->party()->associate($party);
        });

        static::updating(function (Technician $technician): void {
            $identity = $technician->pullVirtualIdentity();

            if ($technician->party_id === null && ! empty($identity)) {
                $displayName = trim((string) ($identity['name'] ?? ''));
                $displayName = $displayName !== '' ? $displayName : 'Técnico';

                $party = Party::query()->create([
                    'type' => 'person',
                    'display_name' => $displayName,
                    'is_active' => (bool) $technician->is_active,
                ]);

                $technician->party()->associate($party);
            }

            if ($technician->party !== null) {
                $displayName = trim((string) ($identity['name'] ?? $technician->party->display_name));
                $displayName = $displayName !== '' ? $displayName : 'Técnico';

                $technician->party->update([
                    'display_name' => $displayName,
                    'is_active' => (bool) $technician->is_active,
                ]);

                $person = $technician->party->person;
                if ($person !== null) {
                    $person->update(['full_name' => $displayName]);
                } else {
                    $technician->party->person()->create(['full_name' => $displayName]);
                }

                if (array_key_exists('email', $identity)) {
                    $thisEmail = trim((string) ($identity['email'] ?? ''));
                    $primaryEmail = $technician->party->emails()->where('is_primary', true)->first();

                    if ($thisEmail === '') {
                        if ($primaryEmail !== null) {
                            $primaryEmail->delete();
                        }
                    } elseif ($primaryEmail !== null) {
                        $primaryEmail->update([
                            'email' => Str::lower($thisEmail),
                        ]);
                    } else {
                        $technician->party->emails()->create([
                            'email' => Str::lower($thisEmail),
                            'type' => 'primary',
                            'is_primary' => true,
                            'is_verified' => false,
                        ]);
                    }
                }

                if (array_key_exists('phone', $identity)) {
                    $thisPhone = trim((string) ($identity['phone'] ?? ''));
                    $primaryPhone = $technician->party->phones()->where('is_primary', true)->first();

                    if ($thisPhone === '') {
                        if ($primaryPhone !== null) {
                            $primaryPhone->delete();
                        }
                    } elseif ($primaryPhone !== null) {
                        $primaryPhone->update([
                            'phone_number' => $thisPhone,
                        ]);
                    } else {
                        $technician->party->phones()->create([
                            'phone_number' => $thisPhone,
                            'type' => 'primary',
                            'is_primary' => true,
                        ]);
                    }
                }
            }
        });
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(TechnicianAvailability::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'party_id', 'party_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(TechnicianBlock::class);
    }

    public function scopeThatAreActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByNameOrEmail(Builder $query, string $term): Builder
    {
        $pattern = "%{$term}%";
        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where(function (Builder $q) use ($pattern, $operator) {
            $q->whereHas('party', function (Builder $partyQuery) use ($pattern, $operator): void {
                $partyQuery->where('display_name', $operator, $pattern)
                    ->orWhereHas('emails', function (Builder $emailQuery) use ($pattern, $operator): void {
                        $emailQuery->where('email', $operator, $pattern);
                    });
            });
        });
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: function () {
                $this->loadMissing('party');

                return $this->party?->display_name;
            },
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: function () {
                $this->loadMissing('party.emails');

                return $this->party?->emails?->firstWhere('is_primary', true)?->email;
            },
        );
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            get: function () {
                $this->loadMissing('party.phones');

                return $this->party?->phones?->firstWhere('is_primary', true)?->phone_number;
            },
        );
    }

    /**
     * @return array{name?: mixed,email?: mixed,phone?: mixed}
     */
    private function pullVirtualIdentity(): array
    {
        $identity = [];

        foreach (['name', 'email', 'phone'] as $key) {
            if (array_key_exists($key, $this->attributes)) {
                $identity[$key] = $this->attributes[$key];
                unset($this->attributes[$key]);
            }
        }

        return $identity;
    }
}
