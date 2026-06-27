<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aid_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('aid_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_file_id')->nullable()->constrained()->nullOnDelete();
            $table->string('distribution_number');
            $table->string('status')->default('draft')->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('delivery_method')->default('pickup');
            $table->string('proof_type')->nullable();
            $table->string('proof_file_path')->nullable();
            $table->string('beneficiary_signature_path')->nullable();
            $table->string('otp_code')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'distribution_number']);
            $table->index(['organization_id', 'aid_batch_id', 'status'], 'aid_dist_org_batch_status_idx');
            $table->index(['organization_id', 'beneficiary_id', 'status'], 'aid_dist_org_ben_status_idx');
            $table->index(['organization_id', 'scheduled_at'], 'aid_dist_org_schedule_idx');
            $table->index(['organization_id', 'delivered_by'], 'aid_dist_org_delivered_by_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aid_distributions');
    }
};
