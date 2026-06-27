<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report_type');
            $table->string('format')->default('csv');
            $table->json('filters')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'report_type'], 'exports_org_report_idx');
            $table->index(['organization_id', 'user_id'], 'exports_org_user_idx');
            $table->index(['organization_id', 'status', 'created_at'], 'exports_org_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
