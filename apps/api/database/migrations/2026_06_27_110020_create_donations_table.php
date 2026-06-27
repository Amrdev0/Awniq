<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('donation_number');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('EGP');
            $table->string('payment_method')->default('cash');
            $table->string('payment_status')->default('pending')->index();
            $table->string('donation_status')->default('pending')->index();
            $table->timestamp('donated_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'donation_number']);
            $table->index(['organization_id', 'donor_id']);
            $table->index(['organization_id', 'campaign_id']);
            $table->index(['organization_id', 'payment_status', 'donation_status']);
            $table->index(['organization_id', 'donated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
