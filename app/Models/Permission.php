<?php

namespace App\Models;

use App\Observers\PermissionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission as SpatiePermission;

#[ObservedBy([PermissionObserver::class])]
class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'module',
        'action',
    ];

    public function scopeBySearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || $search === '') {
            return $query;
        }

        $pattern = "%{$search}%";

        return $query->where(function (Builder $builder) use ($pattern): void {
            $builder->where('name', 'like', $pattern)
                ->orWhere('description', 'like', $pattern)
                ->orWhere('module', 'like', $pattern)
                ->orWhere('action', 'like', $pattern);
        });
    }
}
