<?php

namespace Database\Seeders;

use App\Models\Beneficiary;
use App\Models\Branch;
use App\Models\CaseFile;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCaseManagementSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'hope-bridge-foundation')->firstOrFail();
        $cairoBranch = Branch::where('organization_id', $organization->id)->where('code', 'CAI')->firstOrFail();
        $alexBranch = Branch::where('organization_id', $organization->id)->where('code', 'ALX')->firstOrFail();
        $admin = User::where('email', 'admin@awniq.test')->firstOrFail();
        $caseManager = User::where('email', 'case.manager@awniq.test')->firstOrFail();
        $auditor = User::where('email', 'auditor@awniq.test')->firstOrFail();

        $beneficiaryRows = [
            [
                'code' => 'BEN-000001',
                'branch_id' => $cairoBranch->id,
                'full_name' => 'Mariam Hassan',
                'national_id' => 'EG-DEMO-100001',
                'birth_date' => '1986-04-12',
                'gender' => 'female',
                'phone' => '+20 100 111 0001',
                'city' => 'Cairo',
                'district' => 'Nasr City',
                'address' => 'Demo residential address 1',
                'marital_status' => 'widowed',
                'employment_status' => 'unemployed',
                'monthly_income' => 1200,
                'household_size' => 4,
                'vulnerability_level' => 'critical',
                'status' => 'approved',
                'reviewed_by_user_id' => $caseManager->id,
                'approved_by_user_id' => $admin->id,
                'rejection_reason' => null,
            ],
            [
                'code' => 'BEN-000002',
                'branch_id' => $cairoBranch->id,
                'full_name' => 'Youssef Ali',
                'national_id' => 'EG-DEMO-100002',
                'birth_date' => '1978-09-22',
                'gender' => 'male',
                'phone' => '+20 100 111 0002',
                'city' => 'Cairo',
                'district' => 'Maadi',
                'address' => 'Demo residential address 2',
                'marital_status' => 'married',
                'employment_status' => 'self_employed',
                'monthly_income' => 2200,
                'household_size' => 5,
                'vulnerability_level' => 'high',
                'status' => 'pending_review',
                'reviewed_by_user_id' => null,
                'approved_by_user_id' => null,
                'rejection_reason' => null,
            ],
            [
                'code' => 'BEN-000003',
                'branch_id' => $alexBranch->id,
                'full_name' => 'Nour Mostafa',
                'national_id' => 'EG-DEMO-100003',
                'birth_date' => '1993-01-18',
                'gender' => 'female',
                'phone' => '+20 100 111 0003',
                'city' => 'Alexandria',
                'district' => 'Sidi Gaber',
                'address' => 'Demo residential address 3',
                'marital_status' => 'single',
                'employment_status' => 'unable_to_work',
                'monthly_income' => 0,
                'household_size' => 2,
                'vulnerability_level' => 'critical',
                'status' => 'draft',
                'reviewed_by_user_id' => null,
                'approved_by_user_id' => null,
                'rejection_reason' => null,
            ],
            [
                'code' => 'BEN-000004',
                'branch_id' => $alexBranch->id,
                'full_name' => 'Karim Ibrahim',
                'national_id' => 'EG-DEMO-100004',
                'birth_date' => '1968-11-03',
                'gender' => 'male',
                'phone' => '+20 100 111 0004',
                'city' => 'Alexandria',
                'district' => 'Smouha',
                'address' => 'Demo residential address 4',
                'marital_status' => 'married',
                'employment_status' => 'retired',
                'monthly_income' => 1800,
                'household_size' => 3,
                'vulnerability_level' => 'medium',
                'status' => 'rejected',
                'reviewed_by_user_id' => $caseManager->id,
                'approved_by_user_id' => null,
                'rejection_reason' => 'Missing required household verification documents.',
            ],
            [
                'code' => 'BEN-000005',
                'branch_id' => $cairoBranch->id,
                'full_name' => 'Salma Adel',
                'national_id' => 'EG-DEMO-100005',
                'birth_date' => '1990-07-15',
                'gender' => 'female',
                'phone' => '+20 100 111 0005',
                'city' => 'Cairo',
                'district' => 'Helwan',
                'address' => 'Demo residential address 5',
                'marital_status' => 'divorced',
                'employment_status' => 'employed',
                'monthly_income' => 3000,
                'household_size' => 2,
                'vulnerability_level' => 'low',
                'status' => 'suspended',
                'reviewed_by_user_id' => $auditor->id,
                'approved_by_user_id' => $admin->id,
                'rejection_reason' => null,
            ],
        ];

        foreach ($beneficiaryRows as $row) {
            $beneficiary = Beneficiary::updateOrCreate(
                ['organization_id' => $organization->id, 'code' => $row['code']],
                [
                    ...$row,
                    'organization_id' => $organization->id,
                    'country' => 'Egypt',
                    'email' => null,
                    'alternate_phone' => null,
                    'created_by' => $caseManager->id,
                ],
            );

            $this->seedFamilyMembers($beneficiary);
        }

        $beneficiaries = Beneficiary::where('organization_id', $organization->id)->get()->keyBy('code');

        $caseRows = [
            [
                'case_number' => 'CASE-000001',
                'beneficiary_code' => 'BEN-000001',
                'case_type' => 'monthly_food_support',
                'priority' => 'urgent',
                'status' => 'approved',
                'assigned_to_user_id' => $caseManager->id,
                'reviewed_by_user_id' => $caseManager->id,
                'approved_by_user_id' => $admin->id,
                'rejection_reason' => null,
                'assessment_summary' => 'Household qualifies for recurring food support based on vulnerability score and income review.',
                'next_follow_up_date' => now()->addMonth()->toDateString(),
            ],
            [
                'case_number' => 'CASE-000002',
                'beneficiary_code' => 'BEN-000002',
                'case_type' => 'medical_assistance',
                'priority' => 'high',
                'status' => 'under_review',
                'assigned_to_user_id' => $caseManager->id,
                'reviewed_by_user_id' => null,
                'approved_by_user_id' => null,
                'rejection_reason' => null,
                'assessment_summary' => 'Medical invoices and doctor report are pending verification.',
                'next_follow_up_date' => now()->addDays(10)->toDateString(),
            ],
            [
                'case_number' => 'CASE-000003',
                'beneficiary_code' => 'BEN-000003',
                'case_type' => 'emergency_cash_support',
                'priority' => 'medium',
                'status' => 'open',
                'assigned_to_user_id' => $caseManager->id,
                'reviewed_by_user_id' => null,
                'approved_by_user_id' => null,
                'rejection_reason' => null,
                'assessment_summary' => 'Initial intake completed. Needs home visit before submission.',
                'next_follow_up_date' => now()->addWeek()->toDateString(),
            ],
            [
                'case_number' => 'CASE-000004',
                'beneficiary_code' => 'BEN-000004',
                'case_type' => 'rent_assistance',
                'priority' => 'medium',
                'status' => 'rejected',
                'assigned_to_user_id' => $caseManager->id,
                'reviewed_by_user_id' => $caseManager->id,
                'approved_by_user_id' => null,
                'rejection_reason' => 'The submitted lease document could not be verified.',
                'assessment_summary' => 'Applicant may reopen the case after providing updated landlord documentation.',
                'next_follow_up_date' => now()->addWeeks(2)->toDateString(),
            ],
            [
                'case_number' => 'CASE-000005',
                'beneficiary_code' => 'BEN-000005',
                'case_type' => 'school_supplies',
                'priority' => 'low',
                'status' => 'closed',
                'assigned_to_user_id' => $caseManager->id,
                'reviewed_by_user_id' => $auditor->id,
                'approved_by_user_id' => $admin->id,
                'rejection_reason' => null,
                'assessment_summary' => 'Case closed after supplies were delivered and receipt was verified.',
                'next_follow_up_date' => null,
            ],
        ];

        foreach ($caseRows as $row) {
            $beneficiary = $beneficiaries->get($row['beneficiary_code']);

            if (! $beneficiary) {
                continue;
            }

            $caseFile = CaseFile::updateOrCreate(
                ['organization_id' => $organization->id, 'case_number' => $row['case_number']],
                [
                    'organization_id' => $organization->id,
                    'beneficiary_id' => $beneficiary->id,
                    'case_type' => $row['case_type'],
                    'priority' => $row['priority'],
                    'status' => $row['status'],
                    'assigned_to_user_id' => $row['assigned_to_user_id'],
                    'reviewed_by_user_id' => $row['reviewed_by_user_id'],
                    'approved_by_user_id' => $row['approved_by_user_id'],
                    'rejection_reason' => $row['rejection_reason'],
                    'assessment_summary' => $row['assessment_summary'],
                    'next_follow_up_date' => $row['next_follow_up_date'],
                ],
            );

            $this->seedCaseNotes($caseFile, $caseManager, $admin);
        }
    }

    private function seedFamilyMembers(Beneficiary $beneficiary): void
    {
        $familyMembers = [
            'BEN-000001' => [
                ['full_name' => 'Omar Hassan', 'relationship' => 'son', 'birth_date' => '2014-05-02', 'gender' => 'male'],
                ['full_name' => 'Laila Hassan', 'relationship' => 'daughter', 'birth_date' => '2017-08-19', 'gender' => 'female'],
            ],
            'BEN-000002' => [
                ['full_name' => 'Hana Ali', 'relationship' => 'spouse', 'birth_date' => '1982-02-25', 'gender' => 'female'],
                ['full_name' => 'Malek Ali', 'relationship' => 'son', 'birth_date' => '2012-10-13', 'gender' => 'male'],
            ],
            'BEN-000003' => [
                ['full_name' => 'Amina Mostafa', 'relationship' => 'mother', 'birth_date' => '1965-06-30', 'gender' => 'female'],
            ],
            'BEN-000004' => [
                ['full_name' => 'Samia Ibrahim', 'relationship' => 'spouse', 'birth_date' => '1971-03-08', 'gender' => 'female'],
            ],
            'BEN-000005' => [
                ['full_name' => 'Farida Adel', 'relationship' => 'daughter', 'birth_date' => '2015-12-01', 'gender' => 'female'],
            ],
        ];

        foreach ($familyMembers[$beneficiary->code] ?? [] as $member) {
            $beneficiary->familyMembers()->updateOrCreate(
                ['full_name' => $member['full_name']],
                [
                    ...$member,
                    'national_id' => null,
                    'education_level' => null,
                    'employment_status' => 'unknown',
                    'health_notes' => null,
                ],
            );
        }
    }

    private function seedCaseNotes(CaseFile $caseFile, User $caseManager, User $admin): void
    {
        $notes = [
            [
                'user_id' => $caseManager->id,
                'note' => 'Initial intake record created from demo assessment data.',
                'visibility' => 'internal',
            ],
            [
                'user_id' => $admin->id,
                'note' => "Current case status is {$caseFile->status}.",
                'visibility' => 'internal',
            ],
        ];

        foreach ($notes as $note) {
            $caseFile->notes()->updateOrCreate(
                ['note' => $note['note']],
                $note,
            );
        }
    }
}
