<?php

namespace App\Http\Controllers\Api\V1\Iam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Iam\StoreUserRequest;
use App\Http\Requests\Iam\UpdateUserPermissionsRequest;
use App\Http\Requests\Iam\UpdateUserProfileRequest;
use App\Http\Requests\Iam\UpdateUserRequest;
use App\Http\Requests\Iam\UpdateUserSkillsRequest;
use App\Http\Requests\Iam\UpdateUserStatusRequest;
use App\Http\Requests\Iam\UserLinkCandidatesRequest;
use App\Models\Party;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use App\Services\Iam\PermissionPresetResolver;
use App\Services\Iam\UserProvisioningService;
use App\Services\Iam\UserTechnicianLinkService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    public function linkCandidates(UserLinkCandidatesRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $name = $request->validated('name');
        $currentUserId = $request->query('exclude_user_id');

        $existingUser = null;

        if (is_string($email)) {
            $existingUser = User::query()
                ->select(['id', 'username', 'full_name', 'email'])
                ->where('email', $email)
                ->when(
                    is_numeric($currentUserId),
                    fn ($query) => $query->where('id', '!=', (int) $currentUserId),
                )
                ->first();
        }

        $parties = Party::query()
            ->select(['id', 'type', 'display_name'])
            ->with([
                'technicians:id,party_id',
                'customers:id,party_id',
                'emails:id,party_id,email,is_primary',
            ])
            ->whereDoesntHave('user')
            ->when(is_string($email), function (Builder $query) use ($email): void {
                $query->whereHas('emails', function ($emailQuery) use ($email): void {
                    $emailQuery->where('email', $email);
                });
            })
            ->when(is_string($name), function (Builder $query) use ($name): void {
                $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $pattern = "%{$name}%";

                $query->where(function (Builder $nameQuery) use ($pattern, $operator): void {
                    $nameQuery->where('display_name', $operator, $pattern)
                        ->orWhereHas('person', function (Builder $personQuery) use ($pattern, $operator): void {
                            $personQuery->where('full_name', $operator, $pattern);
                        })
                        ->orWhereHas('organization', function (Builder $organizationQuery) use ($pattern, $operator): void {
                            $organizationQuery->where('legal_name', $operator, $pattern)
                                ->orWhere('trade_name', $operator, $pattern);
                        });
                });
            })
            ->limit(10)
            ->get()
            ->map(function (Party $party): array {
                $entityTypes = [];

                if ($party->technicians->isNotEmpty()) {
                    $entityTypes[] = 'technician';
                }

                if ($party->customers->isNotEmpty()) {
                    $entityTypes[] = 'customer';
                }

                $primaryEmail = $party->emails->firstWhere('is_primary', true)?->email
                    ?? $party->emails->first()?->email;

                return [
                    'party_id' => $party->id,
                    'display_name' => $party->display_name,
                    'primary_email' => $primaryEmail,
                    'is_active' => (bool) $party->is_active,
                    'party_type' => $party->type,
                    'entity_types' => $entityTypes,
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'email' => $email,
                'name' => $name,
                'existing_user' => $existingUser ? [
                    'id' => $existingUser->id,
                    'username' => $existingUser->username,
                    'full_name' => $existingUser->full_name,
                    'email' => $existingUser->email,
                ] : null,
                'candidates' => $parties,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $search = (string) $request->query('search', '');
        $usernameFilter = (string) $request->query('username', '');
        $fullNameFilter = (string) $request->query('full_name', '');
        $nameFilter = (string) $request->query('name', '');
        $emailFilter = (string) $request->query('email', '');
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        $users = User::query()
            ->with(['profile:id,name,slug', 'skills:id,name,slug'])
            ->when($usernameFilter !== '', function ($query) use ($usernameFilter): void {
                $query->where('username', '=', $usernameFilter);
            })
            ->when($fullNameFilter !== '', function ($query) use ($fullNameFilter): void {
                $query->where('full_name', '=', $fullNameFilter);
            })
            ->when($nameFilter !== '', function ($query) use ($nameFilter): void {
                $query->where('username', '=', $nameFilter);
            })
            ->when($emailFilter !== '', function ($query) use ($emailFilter): void {
                $query->where('email', '=', $emailFilter);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('username', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->through(function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->username,
                    'username' => $user->username,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'profile' => $user->profile ? [
                        'id' => $user->profile->id,
                        'name' => $user->profile->name,
                        'slug' => $user->profile->slug,
                    ] : null,
                    'skills' => $user->skills->map(fn ($skill): array => [
                        'id' => $skill->id,
                        'name' => $skill->name,
                        'slug' => $skill->slug,
                    ])->values(),
                    'created_at' => $user->created_at?->toISOString(),
                ];
            });

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['profile.permissions:id,name', 'profile.skills:id,name,slug', 'skills:id,name,slug']);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->username,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'profile' => $user->profile ? [
                    'id' => $user->profile->id,
                    'name' => $user->profile->name,
                    'slug' => $user->profile->slug,
                ] : null,
                'skills' => $user->skills->map(fn ($skill): array => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'slug' => $skill->slug,
                ])->values(),
                'effective_skills' => $user->effectiveSkills()->map(fn ($skill): array => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'slug' => $skill->slug,
                ])->values(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ],
        ]);
    }

    public function store(
        StoreUserRequest $request,
        UserProvisioningService $userProvisioningService,
        UserTechnicianLinkService $userTechnicianLinkService,
    ): JsonResponse {
        $data = $request->validated();

        $user = $userProvisioningService->provision($data);

        $profile = isset($data['profile_id'])
            ? Profile::query()->find($data['profile_id'])
            : null;

        $user->update([
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->syncSingleProfile($profile);
        $user->skills()->sync($data['skill_ids'] ?? []);

        $userTechnicianLinkService->syncForUser($user);

        return response()->json([
            'message' => 'Usuario creado exitosamente.',
            'data' => [
                'id' => $user->id,
            ],
        ], 201);
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        UserTechnicianLinkService $userTechnicianLinkService,
    ): JsonResponse {
        $data = $request->validated();

        $user->update([
            'name' => $data['username'],
            'username' => $data['username'],
            'full_name' => $data['full_name'] ?? null,
            'email' => $data['email'],
            'is_active' => $data['is_active'] ?? $user->is_active,
        ]);

        if (array_key_exists('profile_id', $data)) {
            $profile = $data['profile_id'] !== null
                ? Profile::query()->find($data['profile_id'])
                : null;

            $user->syncSingleProfile($profile);
        }

        if (array_key_exists('skill_ids', $data)) {
            $user->skills()->sync($data['skill_ids']);
        }

        $userTechnicianLinkService->syncForUser($user);

        return response()->json([
            'message' => 'Usuario actualizado exitosamente.',
        ]);
    }

    public function updateStatus(
        UpdateUserStatusRequest $request,
        User $user,
        UserTechnicianLinkService $userTechnicianLinkService,
    ): JsonResponse {
        $data = $request->validated();

        $user->update([
            'is_active' => (bool) $data['is_active'],
        ]);

        $userTechnicianLinkService->syncForUser($user);

        return response()->json([
            'message' => 'Estado del usuario actualizado.',
        ]);
    }

    public function updateProfile(
        UpdateUserProfileRequest $request,
        User $user,
        UserTechnicianLinkService $userTechnicianLinkService,
    ): JsonResponse {
        $profileId = $request->validated('profile_id');

        $profile = $profileId !== null
            ? Profile::query()->find($profileId)
            : null;

        $user->syncSingleProfile($profile);
        $userTechnicianLinkService->syncForUser($user);

        return response()->json([
            'message' => 'Perfil actualizado exitosamente.',
        ]);
    }

    public function updateSkills(UpdateUserSkillsRequest $request, User $user): JsonResponse
    {
        $skillIds = $request->validated('skill_ids');

        $user->skills()->sync($skillIds);

        return response()->json([
            'message' => 'Habilidades del usuario actualizadas.',
        ]);
    }

    public function permissions(User $user, PermissionPresetResolver $presetResolver): JsonResponse
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

        return response()->json([
            'data' => [
                'permission_ids' => $user->permissions()->pluck('id')->values(),
            ],
            'meta' => [
                'capabilities' => $permissionData['capabilities'],
                'warnings' => $permissionData['warnings'],
            ],
        ]);
    }

    public function updatePermissions(
        UpdateUserPermissionsRequest $request,
        User $user,
        PermissionPresetResolver $presetResolver,
    ): JsonResponse {
        $permissionIds = $request->validated('permission_ids', []);
        $selectedCapabilityKeys = $request->validated('selected_capability_keys', []);

        $finalPermissionIds = $presetResolver->resolveFinalPermissionIds($selectedCapabilityKeys, $permissionIds);

        $permissions = Permission::query()
            ->whereIn('id', $finalPermissionIds)
            ->pluck('name')
            ->all();

        $user->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'message' => 'Permisos del usuario actualizados.',
        ]);
    }
}
