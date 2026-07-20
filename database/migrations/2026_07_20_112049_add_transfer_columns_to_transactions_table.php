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
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('transfer_group_id')->nullable()->after('type')->index();
            $table->string('direction', 10)->nullable()->after('transfer_group_id');
            $table->foreignId('counterparty_account_id')->nullable()->after('direction')
                ->constrained('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('counterparty_account_id');
            $table->dropColumn(['transfer_group_id', 'direction']);
        });
    }
};
