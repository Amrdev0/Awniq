<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('quantity', 14, 3);
            $table->decimal('remaining_quantity', 14, 3);
            $table->decimal('reserved_quantity', 14, 3)->default(0);
            $table->date('expiry_date')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['organization_id', 'warehouse_id', 'inventory_item_id'], 'stock_lots_org_wh_item_idx');
            $table->index(['organization_id', 'expiry_date'], 'stock_lots_org_expiry_idx');
            $table->index(['organization_id', 'source_type'], 'stock_lots_org_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lots');
    }
};
