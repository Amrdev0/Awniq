<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('aid_distribution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stock_lot_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 14, 3)->nullable();
            $table->decimal('cash_amount', 14, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'aid_distribution_id'], 'dist_items_org_distribution_idx');
            $table->index(['organization_id', 'inventory_item_id'], 'dist_items_org_inventory_idx');
            $table->index(['organization_id', 'stock_lot_id'], 'dist_items_org_lot_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_items');
    }
};
