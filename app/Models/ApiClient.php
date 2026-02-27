<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class ApiClient extends Model
{
    protected $fillable = [
        'name',
        'key_hash',
        'active',
        'last_used_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function scopeThatAreActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    protected function keyHash(): Attribute
    {
        return Attribute::set(fn (string $value) => Hash::make($value));
    }

    public function matchesKey(string $plainKey): bool
    {
        return Hash::check($plainKey, $this->key_hash);
    }
}
