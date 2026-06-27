<?php

namespace App\Services\PublicPortal;

use App\Models\Organization;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicStatsService
{
    public function __construct(
        private readonly PublicCampaignService $campaignService,
        private readonly PublicPortalSettingsService $settingsService,
    ) {}

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function stats(Organization $organization, array $settings): array
    {
        return Cache::remember(
            $this->settingsService->statsCacheKey($organization->id),
            now()->addMinutes(5),
            fn (): array => $this->calculate($organization, $settings),
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function calculate(Organization $organization, array $settings): array
    {
        $deliveredDistributions = DB::table('aid_distributions')
            ->where('organization_id', $organization->id)
            ->where('status', 'delivered');

        $totalBeneficiariesHelped = (clone $deliveredDistributions)
            ->distinct('beneficiary_id')
            ->count('beneficiary_id');

        $totalAidDistributions = (clone $deliveredDistributions)->count();

        $totalAidItemsDistributed = DB::table('distribution_items')
            ->join('aid_distributions', 'distribution_items.aid_distribution_id', '=', 'aid_distributions.id')
            ->where('distribution_items.organization_id', $organization->id)
            ->where('aid_distributions.status', 'delivered')
            ->sum('distribution_items.quantity');

        $confirmedDonationTotal = null;
        if ((bool) ($settings['show_donation_totals'] ?? false)) {
            $confirmedDonationTotal = DB::table('donations')
                ->where('organization_id', $organization->id)
                ->where('payment_status', 'paid')
                ->where('donation_status', 'confirmed')
                ->sum('amount');
        }

        return [
            'total_beneficiaries_helped' => $totalBeneficiariesHelped,
            'total_aid_distributions' => $totalAidDistributions,
            'total_aid_items_distributed' => number_format((float) $totalAidItemsDistributed, 3, '.', ''),
            'total_confirmed_donations_collected' => $confirmedDonationTotal === null ? null : number_format((float) $confirmedDonationTotal, 2, '.', ''),
            'currency' => $organization->default_currency,
            'active_campaigns' => $this->campaignService->query($organization, $settings)->where('status', 'active')->count(),
            'completed_campaigns' => (bool) ($settings['show_completed_campaigns'] ?? false)
                ? $this->campaignService->query($organization, $settings)->where('status', 'completed')->count()
                : 0,
            'generated_at' => now()->toISOString(),
        ];
    }
}
