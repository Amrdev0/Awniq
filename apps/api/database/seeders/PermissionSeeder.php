<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'organization.view',
        'organization.update',
        'branches.view',
        'branches.create',
        'branches.update',
        'branches.delete',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'users.disable',
        'users.assign_roles',
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'permissions.view',
        'audit_logs.view',
        'audit_logs.export',
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
        'donors.view',
        'donors.create',
        'donors.update',
        'donors.delete',
        'campaigns.view',
        'campaigns.create',
        'campaigns.update',
        'campaigns.delete',
        'campaigns.activate',
        'campaigns.pause',
        'campaigns.complete',
        'campaigns.cancel',
        'donations.view',
        'donations.create',
        'donations.update',
        'donations.cancel',
        'donations.confirm',
        'donations.refund',
        'donation_allocations.manage',
        'payment_transactions.view',
        'receipts.view',
        'receipts.generate',
        'finance_reports.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
