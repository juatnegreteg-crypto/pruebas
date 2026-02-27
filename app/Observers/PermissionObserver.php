<?php

namespace App\Observers;

use App\Models\Permission;
use App\Models\Profile;

class PermissionObserver
{
    public function created(Permission $permission): void
    {
        $superAdminProfile = Profile::query()
            ->where('guard_name', $permission->guard_name)
            ->where('slug', 'super-admin')
            ->first();

        if (! $superAdminProfile) {
            return;
        }

        $superAdminProfile->permissions()->syncWithoutDetaching([$permission->id]);
    }
}
