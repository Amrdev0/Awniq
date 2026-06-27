<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\StockLot;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_list_inventory_records(): void
    {
        $token = $this->seedAndLogin();

        $this->withToken($token)
            ->getJson('/api/v1/warehouses')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['code' => 'CAI-MAIN']);

        $this->withToken($token)
            ->getJson('/api/v1/inventory-items')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonFragment(['sku' => 'FOOD-RICE-5KG']);

        $this->withToken($token)
            ->getJson('/api/v1/stock/lots')
            ->assertOk()
            ->assertJsonCount(5, 'data');

        $this->withToken($token)
            ->getJson('/api/v1/stock/movements')
            ->assertOk()
            ->assertJsonCount(5, 'data');

        $this->withToken($token)
            ->getJson('/api/v1/stock/summary')
            ->assertOk()
            ->assertJsonFragment(['sku' => 'MED-KIT-BASIC']);
    }

    public function test_receive_stock_requires_expiry_for_tracked_items_and_records_stock_in(): void
    {
        $token = $this->seedAndLogin();
        $warehouse = Warehouse::where('code', 'CAI-MAIN')->firstOrFail();
        $item = InventoryItem::where('sku', 'FOOD-RICE-5KG')->firstOrFail();

        $payload = [
            'warehouse_id' => $warehouse->id,
            'inventory_item_id' => $item->id,
            'quantity' => 25,
            'source_type' => 'donation_in_kind',
            'source_id' => 9001,
            'received_at' => now()->toISOString(),
            'notes' => 'Feature test received stock.',
        ];

        $this->withToken($token)
            ->postJson('/api/v1/stock/movements/receive', $payload)
            ->assertUnprocessable();

        $receiveResponse = $this->withToken($token)
            ->postJson('/api/v1/stock/movements/receive', [
                ...$payload,
                'expiry_date' => now()->addMonths(6)->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.remaining_quantity', '25.000');

        $lotId = $receiveResponse->json('data.id');

        $this->assertDatabaseHas('stock_lots', [
            'id' => $lotId,
            'source_type' => 'donation_in_kind',
            'source_id' => 9001,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'stock_lot_id' => $lotId,
            'movement_type' => 'stock_in',
            'quantity' => 25,
        ]);
    }

    public function test_adjustment_out_decreases_remaining_quantity_and_records_movement(): void
    {
        $token = $this->seedAndLogin();
        $lot = StockLot::where('source_type', 'opening_balance')->where('source_id', 1001)->firstOrFail();

        $this->withToken($token)
            ->postJson('/api/v1/stock/movements/adjust', [
                'warehouse_id' => $lot->warehouse_id,
                'inventory_item_id' => $lot->inventory_item_id,
                'stock_lot_id' => $lot->id,
                'movement_type' => 'adjustment_out',
                'quantity' => 15,
                'notes' => 'Feature test correction.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.movement_type', 'adjustment_out');

        $this->assertSame('165.000', $lot->fresh()->remaining_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'stock_lot_id' => $lot->id,
            'movement_type' => 'adjustment_out',
            'quantity' => 15,
        ]);
    }

    public function test_adjustment_out_cannot_make_stock_negative(): void
    {
        $token = $this->seedAndLogin();
        $lot = StockLot::where('source_type', 'opening_balance')->where('source_id', 1003)->firstOrFail();

        $this->withToken($token)
            ->postJson('/api/v1/stock/movements/adjust', [
                'warehouse_id' => $lot->warehouse_id,
                'inventory_item_id' => $lot->inventory_item_id,
                'stock_lot_id' => $lot->id,
                'movement_type' => 'damaged',
                'quantity' => 999,
                'notes' => 'Feature test negative stock prevention.',
            ])
            ->assertUnprocessable();

        $this->assertSame('12.000', $lot->fresh()->remaining_quantity);
    }

    public function test_low_stock_and_expiring_reports_use_seeded_inventory(): void
    {
        $token = $this->seedAndLogin();

        $this->withToken($token)
            ->getJson('/api/v1/stock/low-stock')
            ->assertOk()
            ->assertJsonFragment(['sku' => 'MED-KIT-BASIC'])
            ->assertJsonFragment(['low_stock' => true]);

        $this->withToken($token)
            ->getJson('/api/v1/stock/expiring?days=30')
            ->assertOk()
            ->assertJsonFragment(['sku' => 'FOOD-RICE-5KG'])
            ->assertJsonFragment(['source_id' => 1001]);
    }

    private function seedAndLogin(): string
    {
        $this->seed(DatabaseSeeder::class);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@awniq.test',
            'password' => 'Password123!',
            'device_name' => 'inventory-feature-test',
        ]);

        $loginResponse->assertOk();

        return $loginResponse->json('data.token');
    }
}
