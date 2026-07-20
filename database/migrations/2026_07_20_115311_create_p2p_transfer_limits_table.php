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
        Schema::create('p2p_transfer_limits', function (Blueprint $table) {
            $table->id();
            $table->decimal('max_per_transaction', 14, 2);
            $table->decimal('max_daily_amount', 14, 2);
            $table->unsignedInteger('max_daily_count');
            $table->decimal('max_monthly_amount', 14, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p2p_transfer_limits');
    }
};
