<?php

namespace App\Http\Controllers\Api\V1\Iam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Iam\StoreProfileRequest;
use App\Http\Requests\Iam\UpdateProfilePermissionsRequest;
use App\Http\Requests\Iam\UpdateProfileRequest;
use App\Http\Requests\Iam\UpdateProfileSkillsRequest;
use App\Models\Permission;
use App\Models\Profile;
use App\Services\Iam\PermissionPresetResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class ProfileController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = (string) $request->query('search', '');

        $profiles = Profile::query()
            ->withCount('assignedUsers')
            ->with(['permissions:id,name', 'skills:id,name,slug'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get()
            ->map(function (Profile $profile): array {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'slug' => $profile->slug,
                    'description' => $profile->description,
                    'is_active' => $profile->is_active,
                    'is_technician_profile' => $profile->is_technician_profile,
                    'users_count' => $profile->assigned_users_count,
                    'permissions' => $profile->permissions->map(fn ($permission): array => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ])->values(),
                    'skills' => $profile->skills->map(fn ($skill): array => [
                        'id' => $skill->id,
                        'name' => $skill->name,
                        'slug' => $skill->slug,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $profiles,
        ]);
    }

    public function store(StoreProfileRequest $request): JsonResponse
    {
        $data = $request->validated();

        $profile = Profile::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_technician_profile' => $data['is_technician_profile'] ?? false,
            'guard_name' => 'web',
        ]);

        return response()->json([
            'message' => 'Perfil creado exitosamente.',
            'data' => [
                'id' => $profile->id,
            ],
        ], 201);
    }

    public function update(UpdateProfileRequest $request, Profile $profile): JsonResponse
    {
        $data = $request->validated();

        $profile->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? $profile->is_active,
            'is_technician_profile' => $data['is_technician_profile'] ?? $profile->is_technician_profile,
        ]);

        return response()->json([
            'message' => 'Perfil actualizado exitosamente.',
        ]);
    }

    public function destroy(Request $request, Profile $profile): JsonResponse
    {
        abort_if(! $request->user()?->can('profiles.update'), 403);

        if ($profile->assignedUsers()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un perfil en uso.',
            ], 422);
        }

        $profile->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'message' => 'Perfil eliminado exitosamente.',
        ]);
    }

    public function updatePermissions(
        UpdateProfilePermissionsRequest $request,
        Profile $profile,
        PermissionPresetResolver $presetResolver,
    ): JsonResponse {
        $permissionIds = $request->validated('permission_ids', []);
        $selectedCapabilityKeys = $request->validated('selected_capability_keys', []);

        $finalPermissionIds = $presetResolver->resolveFinalPermissionIds($selectedCapabilityKeys, $permissionIds);

        $permissions = Permission::query()
            ->whereIn('id', $finalPermissionIds)
            ->pluck('name')
            ->all();

        $profile->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'message' => 'Permisos del perfil actualizados.',
        ]);
    }

    public function updateSkills(UpdateProfileSkillsRequest $request, Profile $profile): JsonResponse
    {
        $skillIds = $request->validated('skill_ids');

        $profile->skills()->sync($skillIds);

        return response()->json([
            'message' => 'Habilidades del perfil actualizadas.',
        ]);
    }
}
