<?php

namespace App\Services\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ReportFilterService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyDateRange(Builder|QueryBuilder $query, array $filters, string $column): Builder|QueryBuilder
    {
        return $query
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate($column, '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate($column, '<=', $date));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyBranch(Builder|QueryBuilder $query, array $filters, string $column = 'branch_id'): Builder|QueryBuilder
    {
        return $query->when($filters['branch_id'] ?? null, fn ($query, int|string $branchId) => $query->where($column, $branchId));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyCampaign(Builder|QueryBuilder $query, array $filters, string $column = 'campaign_id'): Builder|QueryBuilder
    {
        return $query->when($filters['campaign_id'] ?? null, fn ($query, int|string $campaignId) => $query->where($column, $campaignId));
    }
}
