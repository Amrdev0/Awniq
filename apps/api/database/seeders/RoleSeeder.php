<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private array $rolePermissions = [
        'super_admin' => PermissionSeeder::PERMISSIONS,
        'organization_admin' => PermissionSeeder::PERMISSIONS,
        'case_manager' => [
            'organization.view',
            'branches.view',
            'users.view',
        ],
        'finance_officer' => [
            'organization.view',
            'branches.view',
            'users.view',
        ],
        'warehouse_manager' => [
            'organization.view',
            'branches.view',
            'users.view',
        ],
        'distribution_officer' => [
            'organization.view',
            'branches.view',
            'users.view',
        ],
        'volunteer' => [
            'organization.view',
        ],
        'auditor' => [
            'organization.view',
            'branches.view',
            'users.view',
            'roles.view',
            'permissions.view',
            'audit_logs.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->forceFill(['is_protected' => true])->save();
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
