<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_lot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type');
            $table->decimal('quantity', 14, 3);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'warehouse_id', 'inventory_item_id'], 'stock_movements_org_wh_item_idx');
            $table->index(['organization_id', 'movement_type', 'created_at'], 'stock_movements_org_type_date_idx');
            $table->index(['organization_id', 'reference_type', 'reference_id'], 'stock_movements_org_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
