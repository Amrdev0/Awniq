<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('relationship');
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('national_id')->nullable();
            $table->string('education_level')->nullable();
            $table->string('employment_status')->nullable();
            $table->text('health_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_family_members');
    }
};
