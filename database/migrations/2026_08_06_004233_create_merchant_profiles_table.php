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
Schema::create('merchant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('business_name');
            $table->string('category')->nullable(); // ex: restauration, boutique, services...
            $table->string('business_registration_number')->nullable(); // NIF/patente si applicable
            $table->text('address')->nullable();

            // Frais preleves par transaction QR - configurable par
            // marchand, avec repli sur un pourcentage global via
            // settings (meme pattern que P2P/creation de compte).
            $table->decimal('transaction_fee_percentage', 5, 2)->nullable();

            $table->string('status', 20)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Authentification API dediee (Sanctum, guard 'merchant') -
            // separee du login du titulaire du compte, pour permettre une
            // integration caisse/terminal independante de l'app client.
            $table->string('api_password')->nullable();
            $table->timestamp('api_password_changed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_profiles');
    }
};
