<?php

namespace App\Http\Controllers\Api\V1\Iam;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\Iam\PermissionPresetResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request, PermissionPresetResolver $presetResolver): JsonResponse
    {
        $permissions = Permission::query()
            ->bySearch($request->query('search'))
            ->orderBy('module')
            ->orderBy('action')
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission): array => [
                'id' => $permission->id,
                'name' => $permission->name,
                'description' => $permission->description,
                'module' => $permission->module,
                'action' => $permission->action,
            ]);

        $capabilityPayload = $presetResolver->toApiCapabilities($permissions);

        return response()->json([
            'data' => $permissions,
            'meta' => [
                'capabilities' => $capabilityPayload['capabilities'],
                'warnings' => $capabilityPayload['warnings'],
                'catalog_version' => crc32($capabilityPayload['capabilities']->toJson()),
            ],
        ]);
    }
}
