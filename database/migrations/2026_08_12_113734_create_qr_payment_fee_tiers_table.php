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
        Schema::create('qr_payment_fee_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_profile_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            // NULL = palier global, applicable a tout marchand n'ayant
            // aucun palier specifique. Un marchand avec ses propres
            // paliers ignore totalement les paliers globaux.
            $table->decimal('min_amount', 14, 2);
            $table->decimal('max_amount', 14, 2)->nullable();
            $table->decimal('fee_percentage', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_payment_fee_tiers');
    }
};
