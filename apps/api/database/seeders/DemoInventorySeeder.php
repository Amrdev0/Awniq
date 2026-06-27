<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\StockLot;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'hope-bridge-foundation')->firstOrFail();
        $cairoBranch = Branch::where('organization_id', $organization->id)->where('code', 'CAI')->firstOrFail();
        $alexBranch = Branch::where('organization_id', $organization->id)->where('code', 'ALX')->firstOrFail();
        $warehouseManager = User::where('email', 'warehouse@awniq.test')->firstOrFail();

        $warehouses = [
            'cairo-main' => Warehouse::updateOrCreate(
                ['organization_id' => $organization->id, 'code' => 'CAI-MAIN'],
                [
                    'branch_id' => $cairoBranch->id,
                    'name' => 'Cairo Main Warehouse',
                    'address' => 'Cairo branch inventory storage',
                    'manager_user_id' => $warehouseManager->id,
                    'status' => 'active',
                ],
            ),
            'alex-main' => Warehouse::updateOrCreate(
                ['organization_id' => $organization->id, 'code' => 'ALX-MAIN'],
                [
                    'branch_id' => $alexBranch->id,
                    'name' => 'Alexandria Warehouse',
                    'address' => 'Alexandria distribution storage',
                    'manager_user_id' => $warehouseManager->id,
                    'status' => 'active',
                ],
            ),
        ];

        $items = [
            'rice' => InventoryItem::updateOrCreate(
                ['organization_id' => $organization->id, 'sku' => 'FOOD-RICE-5KG'],
                [
                    'name' => 'Rice Bag 5kg',
                    'category' => 'food',
                    'unit' => 'bag',
                    'description' => 'Standard 5kg rice bag for food baskets.',
                    'minimum_stock_level' => 100,
                    'track_expiry' => true,
                    'status' => 'active',
                ],
            ),
            'medical-kit' => InventoryItem::updateOrCreate(
                ['organization_id' => $organization->id, 'sku' => 'MED-KIT-BASIC'],
                [
                    'name' => 'Basic Medical Kit',
                    'category' => 'medical',
                    'unit' => 'kit',
                    'description' => 'Basic medical support kit for approved cases.',
                    'minimum_stock_level' => 25,
                    'track_expiry' => true,
                    'status' => 'active',
                ],
            ),
            'school-kit' => InventoryItem::updateOrCreate(
                ['organization_id' => $organization->id, 'sku' => 'EDU-SCHOOL-KIT'],
                [
                    'name' => 'School Supplies Kit',
                    'category' => 'education',
                    'unit' => 'kit',
                    'description' => 'School support pack for students.',
                    'minimum_stock_level' => 40,
                    'track_expiry' => false,
                    'status' => 'active',
                ],
            ),
            'blanket' => InventoryItem::updateOrCreate(
                ['organization_id' => $organization->id, 'sku' => 'WNT-BLANKET'],
                [
                    'name' => 'Winter Blanket',
                    'category' => 'shelter',
                    'unit' => 'piece',
                    'description' => 'Seasonal blanket for winter response.',
                    'minimum_stock_level' => 50,
                    'track_expiry' => false,
                    'status' => 'active',
                ],
            ),
        ];

        $this->seedLot($organization->id, $warehouses['cairo-main'], $items['rice'], $warehouseManager, 1001, 180, 180, now()->addDays(20)->toDateString());
        $this->seedLot($organization->id, $warehouses['alex-main'], $items['rice'], $warehouseManager, 1002, 120, 120, now()->addMonths(8)->toDateString());
        $this->seedLot($organization->id, $warehouses['cairo-main'], $items['medical-kit'], $warehouseManager, 1003, 12, 12, now()->addMonths(4)->toDateString());
        $this->seedLot($organization->id, $warehouses['cairo-main'], $items['school-kit'], $warehouseManager, 1004, 75, 75);
        $this->seedLot($organization->id, $warehouses['alex-main'], $items['blanket'], $warehouseManager, 1005, 95, 95);
    }

    private function seedLot(
        int $organizationId,
        Warehouse $warehouse,
        InventoryItem $item,
        User $creator,
        int $sourceId,
        float $quantity,
        float $remainingQuantity,
        ?string $expiryDate = null,
    ): void {
        $lot = StockLot::updateOrCreate(
            [
                'organization_id' => $organizationId,
                'source_type' => 'opening_balance',
                'source_id' => $sourceId,
            ],
            [
                'warehouse_id' => $warehouse->id,
                'inventory_item_id' => $item->id,
                'quantity' => $quantity,
                'remaining_quantity' => $remainingQuantity,
                'reserved_quantity' => 0,
                'expiry_date' => $expiryDate,
                'received_at' => now()->subDays(10),
            ],
        );

        StockMovement::updateOrCreate(
            [
                'organization_id' => $organizationId,
                'stock_lot_id' => $lot->id,
                'movement_type' => 'stock_in',
                'reference_type' => 'seed',
                'reference_id' => $sourceId,
            ],
            [
                'warehouse_id' => $warehouse->id,
                'inventory_item_id' => $item->id,
                'quantity' => $quantity,
                'notes' => 'Seeded opening balance.',
                'created_by' => $creator->id,
                'created_at' => now()->subDays(10),
            ],
        );
    }
}
