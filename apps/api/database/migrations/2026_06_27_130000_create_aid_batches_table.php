<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aid_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->date('scheduled_date')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'batch_number']);
            $table->index(['organization_id', 'branch_id', 'status'], 'aid_batches_org_branch_status_idx');
            $table->index(['organization_id', 'warehouse_id', 'status'], 'aid_batches_org_warehouse_status_idx');
            $table->index(['organization_id', 'campaign_id', 'status'], 'aid_batches_org_campaign_status_idx');
            $table->index(['organization_id', 'scheduled_date'], 'aid_batches_org_schedule_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aid_batches');
    }
};
