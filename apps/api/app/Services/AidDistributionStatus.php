<?php

namespace App\Services;

class AidDistributionStatus
{
    /**
     * @return list<string>
     */
    public static function terminal(): array
    {
        return ['delivered', 'failed', 'cancelled'];
    }
}
