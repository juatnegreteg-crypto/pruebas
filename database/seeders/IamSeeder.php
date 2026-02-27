<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Profile;
use Illuminate\Database\Seeder;

class IamSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect($this->permissionCatalog());

        $permissions->each(function (array $permission): void {
            Permission::query()->updateOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                ],
                [
                    'description' => $permission['description'],
                    'module' => $permission['module'],
                    'action' => $permission['action'],
                ],
            );
        });

        $superAdminProfile = Profile::query()->updateOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web'],
            [
                'slug' => 'super-admin',
                'description' => 'Perfil con acceso completo al módulo IAM.',
                'is_active' => true,
                'is_technician_profile' => false,
            ],
        );

        $superAdminProfile->syncPermissions(
            Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $permissions->pluck('name')->all())
                ->pluck('name')
                ->all(),
        );

        $this->syncDefaultProfiles();
    }

    /**
     * @return array<int, array{name: string, description: string, module: string, action: string}>
     */
    private function permissionCatalog(): array
    {
        return [
            ['name' => 'dashboard.view', 'description' => 'Ver dashboard', 'module' => 'dashboard', 'action' => 'view'],

            ['name' => 'appointments.view', 'description' => 'Ver agenda de citas', 'module' => 'appointments', 'action' => 'view'],
            ['name' => 'appointments.availability', 'description' => 'Consultar disponibilidad de citas', 'module' => 'appointments', 'action' => 'availability'],
            ['name' => 'appointments.create', 'description' => 'Crear citas', 'module' => 'appointments', 'action' => 'create'],
            ['name' => 'appointments.reschedule', 'description' => 'Reagendar citas', 'module' => 'appointments', 'action' => 'reschedule'],
            ['name' => 'appointments.reassign_technician', 'description' => 'Reasignar técnico en citas', 'module' => 'appointments', 'action' => 'reassign_technician'],
            ['name' => 'appointments.cancel', 'description' => 'Cancelar citas', 'module' => 'appointments', 'action' => 'cancel'],
            ['name' => 'appointments.confirm', 'description' => 'Confirmar citas', 'module' => 'appointments', 'action' => 'confirm'],

            ['name' => 'schedule.view', 'description' => 'Ver horarios', 'module' => 'schedule', 'action' => 'view'],
            ['name' => 'schedule.availability', 'description' => 'Consultar disponibilidad de horarios', 'module' => 'schedule', 'action' => 'availability'],
            ['name' => 'schedule.update', 'description' => 'Actualizar horario base', 'module' => 'schedule', 'action' => 'update'],
            ['name' => 'schedule.manage_overrides', 'description' => 'Gestionar sobreescrituras de horario', 'module' => 'schedule', 'action' => 'manage_overrides'],

            ['name' => 'customers.view', 'description' => 'Ver clientes', 'module' => 'customers', 'action' => 'view'],
            ['name' => 'customers.create', 'description' => 'Crear clientes', 'module' => 'customers', 'action' => 'create'],
            ['name' => 'customers.update', 'description' => 'Editar clientes', 'module' => 'customers', 'action' => 'update'],
            ['name' => 'customers.delete', 'description' => 'Eliminar clientes', 'module' => 'customers', 'action' => 'delete'],
            ['name' => 'customers.import', 'description' => 'Importar clientes', 'module' => 'customers', 'action' => 'import'],
            ['name' => 'customers.export', 'description' => 'Exportar clientes', 'module' => 'customers', 'action' => 'export'],

            ['name' => 'vehicles.view', 'description' => 'Ver vehículos', 'module' => 'vehicles', 'action' => 'view'],
            ['name' => 'vehicles.create', 'description' => 'Crear vehículos', 'module' => 'vehicles', 'action' => 'create'],
            ['name' => 'vehicles.update', 'description' => 'Editar vehículos', 'module' => 'vehicles', 'action' => 'update'],

            ['name' => 'quotes.view', 'description' => 'Ver cotizaciones', 'module' => 'quotes', 'action' => 'view'],
            ['name' => 'quotes.create', 'description' => 'Crear cotizaciones', 'module' => 'quotes', 'action' => 'create'],
            ['name' => 'quotes.update', 'description' => 'Editar cotizaciones', 'module' => 'quotes', 'action' => 'update'],
            ['name' => 'quotes.delete', 'description' => 'Eliminar cotizaciones', 'module' => 'quotes', 'action' => 'delete'],
            ['name' => 'quotes.confirm', 'description' => 'Confirmar cotizaciones', 'module' => 'quotes', 'action' => 'confirm'],
            ['name' => 'quotes.cancel', 'description' => 'Cancelar cotizaciones', 'module' => 'quotes', 'action' => 'cancel'],
            ['name' => 'quotes.export', 'description' => 'Exportar cotizaciones', 'module' => 'quotes', 'action' => 'export'],
            ['name' => 'quotes.items.add', 'description' => 'Agregar ítems en cotizaciones', 'module' => 'quotes', 'action' => 'add_item'],
            ['name' => 'quotes.items.remove', 'description' => 'Quitar ítems de cotizaciones', 'module' => 'quotes', 'action' => 'remove_item'],

            ['name' => 'products.view', 'description' => 'Ver productos', 'module' => 'products', 'action' => 'view'],
            ['name' => 'products.create', 'description' => 'Crear productos', 'module' => 'products', 'action' => 'create'],
            ['name' => 'products.update', 'description' => 'Editar productos', 'module' => 'products', 'action' => 'update'],
            ['name' => 'products.import', 'description' => 'Importar productos', 'module' => 'products', 'action' => 'import'],
            ['name' => 'products.export', 'description' => 'Exportar productos', 'module' => 'products', 'action' => 'export'],

            ['name' => 'services.view', 'description' => 'Ver servicios', 'module' => 'services', 'action' => 'view'],
            ['name' => 'services.create', 'description' => 'Crear servicios', 'module' => 'services', 'action' => 'create'],
            ['name' => 'services.update', 'description' => 'Editar servicios', 'module' => 'services', 'action' => 'update'],
            ['name' => 'services.import', 'description' => 'Importar servicios', 'module' => 'services', 'action' => 'import'],
            ['name' => 'services.export', 'description' => 'Exportar servicios', 'module' => 'services', 'action' => 'export'],

            ['name' => 'bundles.view', 'description' => 'Ver paquetes', 'module' => 'bundles', 'action' => 'view'],
            ['name' => 'bundles.create', 'description' => 'Crear paquetes', 'module' => 'bundles', 'action' => 'create'],
            ['name' => 'bundles.update', 'description' => 'Editar paquetes', 'module' => 'bundles', 'action' => 'update'],

            ['name' => 'technicians.view', 'description' => 'Ver técnicos', 'module' => 'technicians', 'action' => 'view'],
            ['name' => 'technicians.create', 'description' => 'Crear técnicos', 'module' => 'technicians', 'action' => 'create'],
            ['name' => 'technicians.update', 'description' => 'Editar técnicos', 'module' => 'technicians', 'action' => 'update'],
            ['name' => 'technicians.availability.manage', 'description' => 'Gestionar disponibilidad de técnicos', 'module' => 'technicians', 'action' => 'manage_availability'],
            ['name' => 'technicians.blocks.manage', 'description' => 'Gestionar bloqueos de técnicos', 'module' => 'technicians', 'action' => 'manage_blocks'],

            ['name' => 'users.view', 'description' => 'Ver usuarios', 'module' => 'users', 'action' => 'view'],
            ['name' => 'users.create', 'description' => 'Crear usuarios', 'module' => 'users', 'action' => 'create'],
            ['name' => 'users.update', 'description' => 'Editar usuarios', 'module' => 'users', 'action' => 'update'],
            ['name' => 'users.manage_status', 'description' => 'Gestionar estado de usuarios', 'module' => 'users', 'action' => 'manage_status'],

            ['name' => 'profiles.view', 'description' => 'Ver perfiles', 'module' => 'profiles', 'action' => 'view'],
            ['name' => 'profiles.create', 'description' => 'Crear perfiles', 'module' => 'profiles', 'action' => 'create'],
            ['name' => 'profiles.update', 'description' => 'Editar perfiles', 'module' => 'profiles', 'action' => 'update'],
            ['name' => 'profiles.assign_permissions', 'description' => 'Asignar permisos a perfiles', 'module' => 'profiles', 'action' => 'assign_permissions'],

            ['name' => 'permissions.view', 'description' => 'Ver permisos', 'module' => 'permissions', 'action' => 'view'],

            ['name' => 'skills.view', 'description' => 'Ver skills', 'module' => 'skills', 'action' => 'view'],
            ['name' => 'skills.assign', 'description' => 'Asignar skills', 'module' => 'skills', 'action' => 'assign'],
        ];
    }

    private function syncDefaultProfiles(): void
    {
        foreach ($this->defaultProfileCatalog() as $profileData) {
            $profile = Profile::query()->updateOrCreate(
                ['slug' => $profileData['slug'], 'guard_name' => 'web'],
                [
                    'name' => $profileData['name'],
                    'description' => $profileData['description'],
                    'is_active' => true,
                    'is_technician_profile' => $profileData['is_technician_profile'],
                ],
            );

            $profile->syncPermissions(
                $this->permissionNamesForCapabilities(
                    $profileData['capabilities'],
                    $profileData['include_optional_permissions'],
                ),
            );
        }
    }

    /**
     * @param  array<int, string>  $capabilityKeys
     * @return array<int, string>
     */
    private function permissionNamesForCapabilities(array $capabilityKeys, bool $includeOptionalPermissions): array
    {
        $capabilities = collect(config('iam_catalog.capabilities', []))->keyBy('key');

        return collect($capabilityKeys)
            ->flatMap(function (string $capabilityKey) use ($capabilities, $includeOptionalPermissions): array {
                $capability = $capabilities->get($capabilityKey);

                if (! is_array($capability)) {
                    return [];
                }

                return collect($capability['permissions'] ?? [])
                    ->filter(function (array $permission) use ($includeOptionalPermissions): bool {
                        return ($permission['required'] ?? false) || $includeOptionalPermissions;
                    })
                    ->pluck('name')
                    ->all();
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{
     *     slug: string,
     *     name: string,
     *     description: string,
     *     is_technician_profile: bool,
     *     include_optional_permissions: bool,
     *     capabilities: array<int, string>
     * }>
     */
    private function defaultProfileCatalog(): array
    {
        return [
            [
                'slug' => 'gestor-cotizaciones',
                'name' => 'Gestor de Cotizaciones',
                'description' => 'Gestiona el ciclo completo de cotizaciones con acceso de lectura a catálogos y vehículos relacionados.',
                'is_technician_profile' => false,
                'include_optional_permissions' => true,
                'capabilities' => [
                    'dashboard.access',
                    'quotes.manage',
                ],
            ],
            [
                'slug' => 'consulta-cotizaciones',
                'name' => 'Consulta de Cotizaciones',
                'description' => 'Consulta cotizaciones y dependencias requeridas sin permisos de edición.',
                'is_technician_profile' => false,
                'include_optional_permissions' => false,
                'capabilities' => [
                    'dashboard.access',
                    'quotes.manage',
                ],
            ],
            [
                'slug' => 'operador-agenda',
                'name' => 'Operador de Agenda',
                'description' => 'Gestiona agenda, horarios, técnicos y entidades relacionadas para coordinar citas.',
                'is_technician_profile' => false,
                'include_optional_permissions' => true,
                'capabilities' => [
                    'dashboard.access',
                    'appointments.manage',
                    'schedule.manage',
                    'technicians.manage',
                    'customers.manage',
                    'vehicles.manage',
                ],
            ],
            [
                'slug' => 'gestor-catalogo',
                'name' => 'Gestor de Catálogo',
                'description' => 'Administra productos, servicios y paquetes usados en la operación comercial.',
                'is_technician_profile' => false,
                'include_optional_permissions' => true,
                'capabilities' => [
                    'dashboard.access',
                    'products.manage',
                    'services.manage',
                    'bundles.manage',
                ],
            ],
            [
                'slug' => 'tecnico-operativo',
                'name' => 'Técnico Operativo',
                'description' => 'Consulta agenda y horario de operación con perfil técnico asociado.',
                'is_technician_profile' => true,
                'include_optional_permissions' => false,
                'capabilities' => [
                    'dashboard.access',
                    'appointments.manage',
                    'schedule.manage',
                    'skills.manage',
                ],
            ],
            [
                'slug' => 'administrador-iam',
                'name' => 'Administrador IAM',
                'description' => 'Gestiona usuarios, perfiles y políticas de acceso del sistema.',
                'is_technician_profile' => false,
                'include_optional_permissions' => true,
                'capabilities' => [
                    'dashboard.access',
                    'users.manage_basic',
                    'profiles.manage',
                    'permissions.read',
                    'skills.manage',
                ],
            ],
        ];
    }
}
