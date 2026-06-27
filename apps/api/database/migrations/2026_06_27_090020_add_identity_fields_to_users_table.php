<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('password');
            $table->string('status')->default('active')->after('avatar')->index();
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->softDeletes()->after('updated_at');

            $table->index(['organization_id', 'status']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['organization_id', 'status']);
            $table->dropIndex(['branch_id', 'status']);
            $table->dropColumn([
                'organization_id',
                'branch_id',
                'phone',
                'avatar',
                'status',
                'last_login_at',
                'deleted_at',
            ]);
        });
    }
};
