<?php

namespace App\Services\PublicPortal;

use App\Models\Campaign;
use App\Models\Organization;
use App\Models\PublicDonationIntent;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicDonationService
{
    public function __construct(private readonly PublicCampaignService $campaignService) {}

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $payload
     */
    public function createIntent(Organization $organization, array $settings, array $payload): PublicDonationIntent
    {
        abort_unless((bool) ($settings['donations_enabled'] ?? false), 403, 'Public donations are not enabled.');

        $campaign = $this->resolveCampaign($organization, $settings, $payload['campaign_slug'] ?? null);
        $currency = strtoupper((string) $payload['currency']);
        $supportedCurrency = $campaign?->currency ?? $organization->default_currency;

        if ($currency !== strtoupper($supportedCurrency)) {
            throw ValidationException::withMessages([
                'currency' => "Currency must be {$supportedCurrency}.",
            ]);
        }

        return PublicDonationIntent::create([
            'organization_id' => $organization->id,
            'campaign_id' => $campaign?->id,
            'reference' => $this->generateReference($organization),
            'donor_name' => $payload['donor_name'] ?? null,
            'donor_email' => $payload['donor_email'] ?? null,
            'amount' => $payload['amount'],
            'currency' => $currency,
            'status' => 'pending',
            'metadata' => [
                'source' => 'public_portal',
                'payment_flow' => 'placeholder',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function resolveCampaign(Organization $organization, array $settings, ?string $slug): ?Campaign
    {
        if (! $slug) {
            return null;
        }

        $campaign = $this->campaignService
            ->query($organization, $settings)
            ->where('slug', $slug)
            ->first();

        if (! $campaign || $campaign->status !== 'active') {
            throw ValidationException::withMessages([
                'campaign_slug' => 'Campaign must be public, active, and accepting donation intents.',
            ]);
        }

        return $campaign;
    }

    private function generateReference(Organization $organization): string
    {
        do {
            $reference = 'PDI-'.Str::upper(Str::random(12));
        } while (PublicDonationIntent::where('organization_id', $organization->id)->where('reference', $reference)->exists());

        return $reference;
    }
}
