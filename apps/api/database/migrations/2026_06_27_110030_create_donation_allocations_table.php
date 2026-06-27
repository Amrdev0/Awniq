<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donation_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donation_id')->constrained()->cascadeOnDelete();
            $table->string('allocation_type');
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('beneficiary_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('case_file_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'allocation_type']);
            $table->index(['donation_id', 'allocation_type']);
            $table->index(['organization_id', 'campaign_id']);
            $table->index(['organization_id', 'beneficiary_id']);
            $table->index(['organization_id', 'case_file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_allocations');
    }
};
