<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->string('case_number');
            $table->string('case_type');
            $table->string('priority')->default('medium');
            $table->string('status')->default('open')->index();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('assessment_summary')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'case_number']);
            $table->index(['organization_id', 'status']);
            $table->index(['beneficiary_id', 'status']);
            $table->index(['assigned_to_user_id', 'status']);
            $table->index('next_follow_up_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_files');
    }
};
