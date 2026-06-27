<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Organization;
use App\Models\PublicDonationIntent;
use App\Models\User;
use App\Services\PublicPortal\PublicPortalSettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_organization_endpoint_returns_only_public_fields(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->getJson('/api/v1/public/organization')
            ->assertOk()
            ->assertJsonPath('data.name', 'Hope Bridge Foundation')
            ->assertJsonPath('data.slug', 'hope-bridge-foundation')
            ->assertJsonPath('data.contact.email', 'public@hopebridge.test')
            ->assertJsonPath('data.settings.donations_enabled', false)
            ->assertJsonMissingPath('data.email')
            ->assertJsonMissingPath('data.address')
            ->assertJsonMissing(['email' => 'info@hopebridge.test']);
    }

    public function test_public_campaigns_include_only_public_displayable_campaigns(): void
    {
        $this->seed(DatabaseSeeder::class);
        $organization = Organization::where('slug', 'hope-bridge-foundation')->firstOrFail();

        Campaign::create([
            'organization_id' => $organization->id,
            'title' => 'Public Draft Campaign',
            'slug' => 'public-draft-campaign',
            'description' => 'Should not be exposed.',
            'goal_amount' => 1000,
            'currency' => 'EGP',
            'start_date' => now()->toDateString(),
            'status' => 'draft',
            'visibility' => 'public',
        ]);

        Campaign::create([
            'organization_id' => $organization->id,
            'title' => 'Public Cancelled Campaign',
            'slug' => 'public-cancelled-campaign',
            'description' => 'Should not be exposed.',
            'goal_amount' => 1000,
            'currency' => 'EGP',
            'start_date' => now()->toDateString(),
            'status' => 'cancelled',
            'visibility' => 'public',
        ]);

        $this->getJson('/api/v1/public/campaigns')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'ramadan-food-relief'])
            ->assertJsonMissing(['slug' => 'medical-support'])
            ->assertJsonMissing(['slug' => 'winter-supplies'])
            ->assertJsonMissing(['slug' => 'public-draft-campaign'])
            ->assertJsonMissing(['slug' => 'public-cancelled-campaign']);
    }

    public function test_public_campaign_detail_uses_safe_slug_lookup(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->getJson('/api/v1/public/campaigns/ramadan-food-relief')
            ->assertOk()
            ->assertJsonPath('data.slug', 'ramadan-food-relief')
            ->assertJsonPath('data.collected_amount', '5000.00')
            ->assertJsonPath('data.progress_percentage', 10);

        $this->getJson('/api/v1/public/campaigns/medical-support')->assertNotFound();
        $this->getJson('/api/v1/public/campaigns/unknown-campaign')->assertNotFound();
    }

    public function test_public_stats_and_reports_are_aggregated_and_privacy_safe(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->getJson('/api/v1/public/stats')
            ->assertOk()
            ->assertJsonPath('data.total_confirmed_donations_collected', '8000.00')
            ->assertJsonPath('data.active_campaigns', 1)
            ->assertJsonPath('data.total_beneficiaries_helped', 0)
            ->assertJsonMissing(['name' => 'Layla Mahmoud'])
            ->assertJsonMissing(['full_name' => 'Mona Hassan']);

        $this->getJson('/api/v1/public/reports')
            ->assertOk()
            ->assertJsonPath('data.stats.active_campaigns', 1)
            ->assertJsonMissing(['name' => 'Layla Mahmoud'])
            ->assertJsonMissing(['email' => 'layla.donor@example.test']);
    }

    public function test_public_donation_intent_respects_settings_and_validation(): void
    {
        $this->seed(DatabaseSeeder::class);
        $organization = Organization::where('slug', 'hope-bridge-foundation')->firstOrFail();
        $settingsService = app(PublicPortalSettingsService::class);

        $payload = [
            'campaign_slug' => 'ramadan-food-relief',
            'donor_name' => 'Public Donor',
            'donor_email' => 'public.donor@example.test',
            'amount' => 250,
            'currency' => 'EGP',
        ];

        $this->postJson('/api/v1/public/donations', $payload)->assertForbidden();

        $settingsService->update($organization, ['donations_enabled' => true]);

        $this->postJson('/api/v1/public/donations', [...$payload, 'unexpected' => 'private'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payload');

        $this->postJson('/api/v1/public/donations', [...$payload, 'campaign_slug' => 'medical-support'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('campaign_slug');

        $this->postJson('/api/v1/public/donations', $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonMissing(['donor_email' => 'public.donor@example.test']);

        $this->assertDatabaseHas('public_donation_intents', [
            'organization_id' => $organization->id,
            'donor_email' => 'public.donor@example.test',
            'amount' => 250,
            'currency' => 'EGP',
            'status' => 'pending',
        ]);

        $this->assertSame(1, PublicDonationIntent::count());
    }

    public function test_public_donation_endpoint_is_rate_limited(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.8']);

        $payload = [
            'amount' => 50,
            'currency' => 'EGP',
        ];

        $rateLimited = false;

        for ($attempt = 0; $attempt < 12; $attempt++) {
            $response = $this->postJson('/api/v1/public/donations', $payload);

            if ($response->getStatusCode() === 429) {
                $rateLimited = true;
                break;
            }

            $response->assertForbidden();
        }

        $this->assertTrue($rateLimited, 'Expected the public donation endpoint to return HTTP 429 after repeated requests.');
    }

    public function test_admin_public_settings_update_requires_permission(): void
    {
        $this->seed(DatabaseSeeder::class);
        $volunteer = User::where('email', 'volunteer@awniq.test')->firstOrFail();
        $admin = User::where('email', 'admin@awniq.test')->firstOrFail();

        $this->assertTrue($admin->can('public_portal_settings.view'));

        Sanctum::actingAs($volunteer);
        $this->patchJson('/api/v1/settings/public-portal', ['enabled' => false])->assertForbidden();

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/settings/public-portal')
            ->assertOk()
            ->assertJsonPath('data.enabled', true);

        $this->patchJson('/api/v1/settings/public-portal', ['enabled' => false, 'reports_enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.reports_enabled', false);

        $this->getJson('/api/v1/public/organization')->assertNotFound();
    }
}
