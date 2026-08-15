<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('activation_code_hash')->nullable()->after('password');
            $table->timestamp('activation_expires_at')->nullable()->after('activation_code_hash');
            $table->timestamp('activated_at')->nullable()->after('activation_expires_at');
            $table->timestamp('password_changed_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'activation_code_hash', 'activation_expires_at',
                'activated_at', 'password_changed_at',
            ]);
        });
    }
};
