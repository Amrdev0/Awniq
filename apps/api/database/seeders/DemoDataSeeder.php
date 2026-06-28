<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed fictional data for local demos and manual release smoke tests.
     */
    public function run(): void
    {
        $this->call([
            DemoIdentitySeeder::class,
            DemoCaseManagementSeeder::class,
            DemoFinanceSeeder::class,
            DemoInventorySeeder::class,
            DemoAidDistributionSeeder::class,
        ]);
    }
}
