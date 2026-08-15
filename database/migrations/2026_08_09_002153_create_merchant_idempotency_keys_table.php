<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_api_key_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key');
            $table->string('request_hash', 64);
            $table->unsignedSmallInteger('response_status');
            $table->json('response_body');
            $table->timestamp('expires_at');

            $table->timestamps();

            // Nom court explicite - le nom auto-genere par Laravel
            // (merchant_idempotency_keys_merchant_api_key_id_idempotency_key_unique)
            // depasse la limite de 64 caracteres de MySQL/MariaDB.
            $table->unique(['merchant_api_key_id', 'idempotency_key'], 'merchant_idem_keys_api_key_idem_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_idempotency_keys');
    }
};
