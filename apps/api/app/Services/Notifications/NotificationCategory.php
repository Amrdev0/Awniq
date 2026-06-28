<?php

namespace App\Services\Notifications;

class NotificationCategory
{
    public const CASES = 'cases';

    public const FINANCE = 'finance';

    public const INVENTORY = 'inventory';

    public const AID_DISTRIBUTION = 'aid_distribution';

    public const SYSTEM = 'system';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CASES,
            self::FINANCE,
            self::INVENTORY,
            self::AID_DISTRIBUTION,
            self::SYSTEM,
        ];
    }
}
