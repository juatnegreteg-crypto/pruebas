<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use App\Services\Iam\PermissionPresetResolver;
use Inertia\Inertia;
use Inertia\Response;

class IamPageController extends Controller
{
    public function users(): Response
    {
        return Inertia::render('iam/users/Index');
    }

    public function profiles(): Response
    {
        return Inertia::render('iam/profiles/Index');
    }

    public function profileCapabilities(Profile $profile, PermissionPresetResolver $presetResolver): Response
    {
        $permissionData = $presetResolver->toApiCapabilities(
            Permission::query()
                ->select(['id', 'name'])
                ->get()
                ->map(fn (Permission $permission): array => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ]),
        );

        return Inertia::render('iam/profiles/Capabilities', [
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'description' => $profile->description,
            ],
            'permissionIds' => $profile->permissions()->pluck('id')->values(),
            'capabilities' => $permissionData['capabilities'],
            'warnings' => $permissionData['warnings'],
        ]);
    }

    public function userCapabilities(User $user, PermissionPresetResolver $presetResolver): Response
    {
        $permissionData = $presetResolver->toApiCapabilities(
            Permission::query()
                ->select(['id', 'name'])
                ->get()
                ->map(fn (Permission $permission): array => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ]),
        );

        return Inertia::render('iam/users/Capabilities', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'full_name' => $user->full_name,
                'email' => $user->email,
            ],
            'permissionIds' => $user->permissions()->pluck('id')->values(),
            'capabilities' => $permissionData['capabilities'],
            'warnings' => $permissionData['warnings'],
        ]);
    }

    public function permissions(): Response
    {
        return Inertia::render('iam/permissions/Index');
    }

    public function skills(): Response
    {
        return Inertia::render('iam/skills/Index');
    }
}
