<?php

namespace App\Http\Requests\Concerns;

trait AuthorizesPermission
{
    protected function authorizesPermission(string $permission): bool
    {
        $user = $this->user();

        return $user !== null && $user->is_active && $user->can($permission);
    }
}
