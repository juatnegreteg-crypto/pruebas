<?php

namespace App\Http\Controllers\Web;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetupBootstrapRequest;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use App\Services\Iam\UserPartySyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('setup/Index');
    }

    public function store(
        SetupBootstrapRequest $request,
        CreateNewUser $createNewUser,
        UserPartySyncService $userPartySyncService,
    ): RedirectResponse {
        $user = DB::transaction(function () use ($createNewUser, $request, $userPartySyncService): User {
            $superAdminProfile = Profile::query()->updateOrCreate(
                [
                    'name' => 'Super Admin',
                    'guard_name' => 'web',
                ],
                [
                    'slug' => 'super-admin',
                    'description' => 'Perfil con acceso completo al módulo IAM.',
                    'is_active' => true,
                    'is_technician_profile' => false,
                ],
            );

            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->pluck('name')
                ->all();

            $superAdminProfile->syncPermissions($permissions);

            $user = $createNewUser->create($request->validated());
            $user->forceFill([
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();
            $userPartySyncService->syncFromUser($user);
            $user->syncSingleProfile($superAdminProfile);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('dashboard');
    }
}
