<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Support\Collection;

class NotificationRoutingService
{
    /**
     * @return Collection<int, User>
     */
    public function organizationAdmins(int $organizationId): Collection
    {
        return $this->activeUsers($organizationId, ['organization_admin', 'super_admin']);
    }

    /**
     * @return Collection<int, User>
     */
    public function caseManagers(int $organizationId, ?int $branchId = null): Collection
    {
        return $this->activeUsers($organizationId, ['case_manager', 'organization_admin'], $branchId);
    }

    /**
     * @return Collection<int, User>
     */
    public function financeUsers(int $organizationId): Collection
    {
        return $this->activeUsers($organizationId, ['finance_officer', 'organization_admin']);
    }

    /**
     * @return Collection<int, User>
     */
    public function warehouseUsers(int $organizationId, ?int $branchId = null): Collection
    {
        return $this->activeUsers($organizationId, ['warehouse_manager', 'organization_admin'], $branchId);
    }

    /**
     * @return Collection<int, User>
     */
    public function distributionUsers(int $organizationId, ?int $branchId = null): Collection
    {
        return $this->activeUsers($organizationId, ['distribution_officer', 'organization_admin'], $branchId);
    }

    /**
     * @param  list<string>  $roles
     * @return Collection<int, User>
     */
    private function activeUsers(int $organizationId, array $roles, ?int $branchId = null): Collection
    {
        return User::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $roles))
            ->when($branchId, fn ($query, int $branchId) => $query->where(function ($query) use ($branchId): void {
                $query->whereNull('branch_id')->orWhere('branch_id', $branchId);
            }))
            ->get();
    }
}
