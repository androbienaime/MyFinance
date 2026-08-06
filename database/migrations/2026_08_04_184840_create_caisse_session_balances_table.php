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
        Schema::create('caisse_session_balances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('caisse_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();

            // Compte physique du caissier a l'ouverture, pour cette devise
            $table->decimal('opening_balance_declared', 15, 2)->default(0);
            // Attendu = solde de fermeture declare de la veille, meme devise
            $table->decimal('opening_balance_expected', 15, 2)->default(0);
            $table->decimal('opening_discrepancy', 15, 2)->default(0);

            // Mouvements de la journee dans cette devise, calcules a la fermeture
            $table->decimal('total_deposits', 15, 2)->default(0);
            $table->decimal('total_withdrawals', 15, 2)->default(0);
            $table->decimal('total_other_movements', 15, 2)->default(0);

            $table->decimal('closing_balance_expected', 15, 2)->nullable();
            $table->decimal('closing_balance_declared', 15, 2)->nullable();
            $table->decimal('closing_discrepancy', 15, 2)->nullable();
            $table->text('closing_comment')->nullable();

            $table->timestamps();

            // Une seule ligne par devise et par session
            $table->unique(['caisse_session_id', 'currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_session_balances');
    }
};
