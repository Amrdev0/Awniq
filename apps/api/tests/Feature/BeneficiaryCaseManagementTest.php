<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\Branch;
use App\Models\CaseFile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryCaseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_list_beneficiaries_and_cases(): void
    {
        $token = $this->seedAndLogin();

        $this->withToken($token)
            ->getJson('/api/v1/beneficiaries')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonFragment(['code' => 'BEN-000001']);

        $this->withToken($token)
            ->getJson('/api/v1/case-files')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonFragment(['case_number' => 'CASE-000001']);
    }

    public function test_admin_can_create_and_approve_beneficiary(): void
    {
        $token = $this->seedAndLogin();
        $branch = Branch::where('code', 'CAI')->firstOrFail();

        $createResponse = $this->withToken($token)
            ->postJson('/api/v1/beneficiaries', [
                'branch_id' => $branch->id,
                'full_name' => 'Test Beneficiary',
                'national_id' => 'EG-DEMO-200001',
                'birth_date' => '1991-02-03',
                'gender' => 'female',
                'phone' => '+20 100 222 0001',
                'country' => 'Egypt',
                'city' => 'Cairo',
                'district' => 'Downtown',
                'address' => 'Manual test address',
                'marital_status' => 'single',
                'employment_status' => 'unemployed',
                'monthly_income' => 500,
                'household_size' => 1,
                'vulnerability_level' => 'high',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.code', 'BEN-000006');

        $beneficiaryId = $createResponse->json('data.id');

        $this->withToken($token)
            ->postJson("/api/v1/beneficiaries/{$beneficiaryId}/submit-review")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_review');

        $this->withToken($token)
            ->postJson("/api/v1/beneficiaries/{$beneficiaryId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_admin_can_create_review_case_and_add_note(): void
    {
        $token = $this->seedAndLogin();
        $beneficiary = Beneficiary::where('code', 'BEN-000001')->firstOrFail();

        $createResponse = $this->withToken($token)
            ->postJson('/api/v1/case-files', [
                'beneficiary_id' => $beneficiary->id,
                'case_type' => 'winter_blankets',
                'priority' => 'medium',
                'assessment_summary' => 'Household requested seasonal blanket support.',
                'next_follow_up_date' => now()->addWeek()->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.case_number', 'CASE-000006');

        $caseFileId = $createResponse->json('data.id');

        $this->withToken($token)
            ->postJson("/api/v1/case-files/{$caseFileId}/submit-review")
            ->assertOk()
            ->assertJsonPath('data.status', 'under_review');

        $this->withToken($token)
            ->postJson("/api/v1/case-files/{$caseFileId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->withToken($token)
            ->postJson("/api/v1/case-files/{$caseFileId}/notes", [
                'note' => 'Approved during feature test.',
                'visibility' => 'internal',
            ])
            ->assertCreated()
            ->assertJsonPath('data.note', 'Approved during feature test.');
    }

    public function test_case_rejection_requires_reason(): void
    {
        $token = $this->seedAndLogin();
        $caseFile = CaseFile::where('case_number', 'CASE-000002')->firstOrFail();

        $this->withToken($token)
            ->postJson("/api/v1/case-files/{$caseFile->id}/reject")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');
    }

    private function seedAndLogin(): string
    {
        $this->seed(DatabaseSeeder::class);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@awniq.test',
            'password' => 'Password123!',
            'device_name' => 'feature-test',
        ]);

        $loginResponse->assertOk();

        return $loginResponse->json('data.token');
    }
}
