<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'display_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function person(): HasOne
    {
        return $this->hasOne(PartyPerson::class);
    }

    public function organization(): HasOne
    {
        return $this->hasOne(PartyOrganization::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(PartyEmail::class);
    }

    public function phones(): HasMany
    {
        return $this->hasMany(PartyPhone::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PartyAddress::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function technicians(): HasMany
    {
        return $this->hasMany(Technician::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
