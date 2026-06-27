<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('donor_type')->default('individual');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->text('notes')->nullable();
            $table->json('communication_preferences')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'donor_type', 'status']);
            $table->index(['organization_id', 'name']);
            $table->index(['organization_id', 'email']);
            $table->index(['organization_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donors');
    }
};
