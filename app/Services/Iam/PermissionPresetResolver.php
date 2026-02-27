<?php

namespace App\Services\Iam;

use App\Models\Permission;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PermissionPresetResolver
{
    public function capabilityKeys(): array
    {
        return $this->capabilities()->pluck('key')->all();
    }

    /**
     * @return array{
     *     capabilities: \Illuminate\Support\Collection<int, array<string, mixed>>,
     *     warnings: \Illuminate\Support\Collection<int, array{capability_key: string, permission_name: string, required: bool}>
     * }
     */
    public function toApiCapabilities(Collection $permissions): array
    {
        $permissionsByName = $permissions->mapWithKeys(fn (array $permission): array => [$permission['name'] => $permission]);
        $warnings = collect();

        $capabilities = $this->capabilities()
            ->map(function (array $capability) use ($permissionsByName, $warnings): array {
                $resolvedPermissions = collect($capability['permissions'])
                    ->map(function (array $permission) use ($permissionsByName, $capability, $warnings): ?array {
                        $resolved = $permissionsByName->get($permission['name']);

                        if (! is_array($resolved)) {
                            $warnings->push([
                                'capability_key' => $capability['key'],
                                'permission_name' => $permission['name'],
                                'required' => $permission['required'],
                            ]);

                            return null;
                        }

                        return [
                            'id' => $resolved['id'],
                            'name' => $resolved['name'],
                            'required' => $permission['required'],
                        ];
                    })
                    ->filter()
                    ->values();

                return [
                    'key' => $capability['key'],
                    'label' => $capability['label'],
                    'description' => $capability['description'],
                    'order' => $capability['order'],
                    'permissions' => $resolvedPermissions->all(),
                ];
            })
            ->sortBy('order')
            ->values();

        return [
            'capabilities' => $capabilities,
            'warnings' => $warnings->values(),
        ];
    }

    /**
     * @param  array<int, string>  $selectedCapabilityKeys
     * @param  array<int, int>  $manualPermissionIds
     * @return array<int, int>
     */
    public function resolveFinalPermissionIds(array $selectedCapabilityKeys, array $manualPermissionIds): array
    {
        $selectedCapabilities = $this->capabilities()
            ->whereIn('key', $selectedCapabilityKeys)
            ->values();

        $requiredPermissionNames = $selectedCapabilities
            ->flatMap(function (array $capability): Collection {
                return collect($capability['permissions'])
                    ->filter(fn (array $permission): bool => $permission['required'])
                    ->pluck('name');
            })
            ->unique()
            ->values();

        $requiredPermissionIds = Permission::query()
            ->whereIn('name', $requiredPermissionNames->all())
            ->pluck('id', 'name');

        $missingRequiredNames = $requiredPermissionNames
            ->filter(fn (string $name): bool => ! $requiredPermissionIds->has($name))
            ->values();

        if ($missingRequiredNames->isNotEmpty()) {
            throw ValidationException::withMessages([
                'selected_capability_keys' => [
                    'Faltan permisos requeridos en base de datos: '.$missingRequiredNames->join(', '),
                ],
            ]);
        }

        return collect($manualPermissionIds)
            ->merge($requiredPermissionIds->values()->all())
            ->unique()
            ->map(fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    private function capabilities(): Collection
    {
        return collect(config('iam_catalog.capabilities', []))
            ->map(fn (array $capability): array => $this->normalizeCapability($capability))
            ->values();
    }

    private function normalizeCapability(array $capability): array
    {
        $key = (string) ($capability['key'] ?? '');
        $permissions = collect($capability['permissions'] ?? [])
            ->map(function (mixed $permission): array {
                if (is_string($permission)) {
                    return [
                        'name' => $permission,
                        'required' => true,
                    ];
                }

                return [
                    'name' => (string) ($permission['name'] ?? ''),
                    'required' => (bool) ($permission['required'] ?? false),
                ];
            })
            ->filter(fn (array $permission): bool => $permission['name'] !== '')
            ->unique(fn (array $permission): string => $permission['name'])
            ->values()
            ->all();

        return [
            'key' => $key,
            'label' => (string) ($capability['label'] ?? $key),
            'description' => $capability['description'] ?? null,
            'order' => (int) ($capability['order'] ?? 100),
            'permissions' => $permissions,
        ];
    }
}
