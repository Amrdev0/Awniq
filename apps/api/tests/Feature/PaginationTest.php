<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Organization;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_pagination_exposes_stable_metadata_and_clamps_page_size(): void
    {
        $this->seed(DatabaseSeeder::class);
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@awniq.test',
            'password' => 'Password123!',
            'device_name' => 'pagination-test',
        ])->assertOk()->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/beneficiaries?page=2&per_page=2')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.from', 3)
            ->assertJsonCount(2, 'data');

        $template = Beneficiary::query()->firstOrFail();
        foreach (range(1, 105) as $index) {
            $copy = $template->replicate();
            $copy->code = sprintf('BEN-PAGE-%06d', $index);
            $copy->national_id = null;
            $copy->full_name = "Pagination Beneficiary {$index}";
            $copy->save();
        }

        $this->withToken($token)
            ->getJson('/api/v1/beneficiaries?per_page=1000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonCount(100, 'data');

        $this->withToken($token)
            ->getJson('/api/v1/beneficiaries?page=999&per_page=15')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 999)
            ->assertJsonCount(0, 'data');

        $this->withToken($token)
            ->getJson('/api/v1/beneficiaries?search=BEN-000001&per_page=15')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'BEN-000001');

        $this->withToken($token)
            ->getJson('/api/v1/beneficiaries?search=BEN-000001&page=2&per_page=15')
            ->assertOk()
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonCount(0, 'data');

        $this->withToken($token)
            ->getJson('/api/v1/stock/summary?page=1&per_page=2')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data');

        $otherOrganization = Organization::create(['name' => 'Other NGO', 'slug' => 'other-pagination-ngo']);
        AuditLog::create(['organization_id' => $otherOrganization->id, 'action' => 'foreign.secret', 'entity_type' => Organization::class]);
        $expectedAuditTotal = AuditLog::where('organization_id', $template->organization_id)->count();

        $this->withToken($token)
            ->getJson('/api/v1/audit-logs?per_page=100')
            ->assertOk()
            ->assertJsonPath('meta.total', $expectedAuditTotal)
            ->assertJsonMissing(['action' => 'foreign.secret']);
    }
}
