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
        Schema::create('merchant_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->index(); // code du compte marchand
            $table->foreignId('merchant_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->index();
            $table->text('user_agent')->nullable();
            $table->enum('status', ['success', 'failed_password', 'blocked', 'account_inactive'])->index();
            $table->timestamp('attempted_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_login_attempts');
    }
};
