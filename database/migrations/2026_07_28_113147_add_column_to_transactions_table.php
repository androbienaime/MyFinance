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
            $table->foreignId('currency_id')->nullable()->after('amount')->constrained('currencies');
            $table->decimal('exchange_rate_applied', 20, 6)->nullable()->after('currency_id');
            // null = pas de conversion (meme devise que le compte de reference du transfert)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign("currency_id");
            $table->dropColumn("exchange_rate_applied");
        });
    }
};
