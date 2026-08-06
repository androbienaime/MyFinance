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
            // PIN optionnel du CLIENT (pas du marchand) - utilise au
            // moment de scanner un QR de paiement, en alternative ou en
            // complement du mot de passe deja utilise pour l'app.
            $table->string('pin_hash')->nullable()->after('password');
            $table->timestamp('pin_set_at')->nullable()->after('pin_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['pin_hash', 'pin_set_at']);
        });
    }
};
