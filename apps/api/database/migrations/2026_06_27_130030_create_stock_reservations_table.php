<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('aid_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('aid_distribution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('distribution_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_lot_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->string('status')->default('reserved')->index();
            $table->timestamps();

            $table->index(['organization_id', 'aid_batch_id', 'status'], 'stock_res_org_batch_status_idx');
            $table->index(['organization_id', 'aid_distribution_id', 'status'], 'stock_res_org_dist_status_idx');
            $table->index(['organization_id', 'stock_lot_id', 'status'], 'stock_res_org_lot_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
