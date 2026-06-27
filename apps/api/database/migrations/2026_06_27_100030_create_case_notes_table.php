<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note');
            $table->string('visibility')->default('internal');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_file_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_notes');
    }
};
