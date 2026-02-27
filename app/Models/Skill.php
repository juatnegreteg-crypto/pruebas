<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'profile_skill', 'skill_id', 'role_id')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'skill_user')
            ->withTimestamps();
    }

    public function scopeBySearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || $search === '') {
            return $query;
        }

        $pattern = "%{$search}%";

        return $query->where(function (Builder $builder) use ($pattern): void {
            $builder->where('name', 'like', $pattern)
                ->orWhere('slug', 'like', $pattern)
                ->orWhere('description', 'like', $pattern);
        });
    }
}
