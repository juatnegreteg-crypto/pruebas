<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

class Profile extends Role
{
    protected $fillable = [
        'name',
        'guard_name',
        'slug',
        'description',
        'is_active',
        'is_technician_profile',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_technician_profile' => 'boolean',
        ];
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.role_pivot_key') ?: 'role_id',
            config('permission.column_names.model_morph_key'),
        )->wherePivot('model_type', User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'profile_skill', 'role_id', 'skill_id')
            ->withTimestamps();
    }

    public function scopeThatAreActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
