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
            'beneficiaries.view',
            'beneficiaries.create',
            'beneficiaries.update',
            'beneficiaries.delete',
            'beneficiaries.submit_review',
            'beneficiaries.approve',
            'beneficiaries.reject',
            'beneficiaries.suspend',
            'beneficiaries.reactivate',
            'beneficiary_family.view',
            'beneficiary_family.manage',
            'case_files.view',
            'case_files.create',
            'case_files.update',
            'case_files.delete',
            'case_files.review',
            'case_files.approve',
            'case_files.reject',
            'case_files.suspend',
            'case_files.close',
            'case_files.reopen',
            'case_notes.view',
            'case_notes.create',
            'case_notes.update',
            'case_notes.delete',
            'case_documents.view',
            'case_documents.upload',
            'case_documents.download',
            'case_documents.delete',
        ],
        'finance_officer' => [
            'organization.view',
            'branches.view',
            'users.view',
            'beneficiaries.view',
            'case_files.view',
            'case_notes.view',
            'case_documents.view',
            'case_documents.download',
        ],
        'warehouse_manager' => [
            'organization.view',
            'branches.view',
            'users.view',
            'beneficiaries.view',
            'case_files.view',
        ],
        'distribution_officer' => [
            'organization.view',
            'branches.view',
            'users.view',
            'beneficiaries.view',
            'case_files.view',
            'case_notes.view',
        ],
        'volunteer' => [
            'organization.view',
            'beneficiaries.view',
            'case_files.view',
        ],
        'auditor' => [
            'organization.view',
            'branches.view',
            'users.view',
            'roles.view',
            'permissions.view',
            'audit_logs.view',
            'beneficiaries.view',
            'beneficiary_family.view',
            'case_files.view',
            'case_notes.view',
            'case_documents.view',
            'case_documents.download',
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
