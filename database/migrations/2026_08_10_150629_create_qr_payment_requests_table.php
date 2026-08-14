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
        Schema::create('qr_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();

            $table->foreignId('merchant_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('description')->nullable();

            $table->decimal('amount', 14, 2); // montant du produit/service, avant frais
            $table->decimal('fee_amount', 14, 2)->default(0); // calcule a la generation, fige jusqu'au paiement
            $table->decimal('total_amount', 14, 2); // amount + fee_amount = ce que le client paie reellement

            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('paying_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('pin_attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['merchant_profile_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_payment_requests');
    }
};
