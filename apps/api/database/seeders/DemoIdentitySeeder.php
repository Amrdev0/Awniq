<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\User;
use App\Services\PublicPortal\PublicPortalSettingsService;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DemoIdentitySeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::updateOrCreate(
            ['slug' => 'hope-bridge-foundation'],
            [
                'name' => 'Hope Bridge Foundation',
                'legal_name' => 'Hope Bridge Foundation',
                'email' => 'info@hopebridge.test',
                'phone' => '+20 100 000 0000',
                'website' => 'https://hopebridge.test',
                'country' => 'Egypt',
                'city' => 'Cairo',
                'address' => 'Demo address for local testing',
                'default_currency' => 'EGP',
                'timezone' => 'Africa/Cairo',
                'language' => 'en',
                'status' => 'active',
            ],
        );

        OrganizationSetting::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'key' => PublicPortalSettingsService::KEY,
            ],
            [
                'value' => [
                    'enabled' => true,
                    'show_donation_totals' => true,
                    'show_campaign_progress' => true,
                    'show_completed_campaigns' => true,
                    'show_contact_info' => true,
                    'donations_enabled' => false,
                    'reports_enabled' => true,
                    'contact_email' => 'public@hopebridge.test',
                    'contact_phone' => '+20 100 000 0100',
                    'about' => 'Hope Bridge Foundation is a demo NGO profile for testing Awniq public transparency features.',
                ],
            ],
        );

        $cairoBranch = Branch::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'CAI'],
            [
                'name' => 'Cairo Branch',
                'phone' => '+20 100 000 0001',
                'email' => 'cairo@hopebridge.test',
                'country' => 'Egypt',
                'city' => 'Cairo',
                'address' => 'Cairo demo branch',
                'status' => 'active',
            ],
        );

        $alexBranch = Branch::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'ALX'],
            [
                'name' => 'Alexandria Branch',
                'phone' => '+20 100 000 0002',
                'email' => 'alex@hopebridge.test',
                'country' => 'Egypt',
                'city' => 'Alexandria',
                'address' => 'Alexandria demo branch',
                'status' => 'active',
            ],
        );

        $users = [
            ['name' => 'System Super Admin', 'email' => 'super@awniq.test', 'role' => 'super_admin', 'branch_id' => null],
            ['name' => 'Organization Admin', 'email' => 'admin@awniq.test', 'role' => 'organization_admin', 'branch_id' => $cairoBranch->id],
            ['name' => 'Case Manager', 'email' => 'case.manager@awniq.test', 'role' => 'case_manager', 'branch_id' => $cairoBranch->id],
            ['name' => 'Finance Officer', 'email' => 'finance@awniq.test', 'role' => 'finance_officer', 'branch_id' => $cairoBranch->id],
            ['name' => 'Warehouse Manager', 'email' => 'warehouse@awniq.test', 'role' => 'warehouse_manager', 'branch_id' => $alexBranch->id],
            ['name' => 'Distribution Officer', 'email' => 'distribution@awniq.test', 'role' => 'distribution_officer', 'branch_id' => $alexBranch->id],
            ['name' => 'Volunteer User', 'email' => 'volunteer@awniq.test', 'role' => 'volunteer', 'branch_id' => $alexBranch->id],
            ['name' => 'Auditor User', 'email' => 'auditor@awniq.test', 'role' => 'auditor', 'branch_id' => null],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'organization_id' => $organization->id,
                    'branch_id' => $userData['branch_id'],
                    'name' => $userData['name'],
                    'phone' => '+20 100 000 0099',
                    'password' => 'Password123!',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$userData['role']]);
        }

        $cairoBranch->update(['manager_user_id' => User::where('email', 'admin@awniq.test')->value('id')]);
        $alexBranch->update(['manager_user_id' => User::where('email', 'warehouse@awniq.test')->value('id')]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
