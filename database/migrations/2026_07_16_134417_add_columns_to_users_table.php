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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('must_change_password');
            $table->timestamp('deactivated_at')->nullable();
            $table->text('deactivation_reason')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn("password_changed_at");
            $table->dropColumn("deactivated_at");
            $table->dropColumn("deactivation_reason");
            $table->dropForeign("deactivated_by");
        });
    }
};
