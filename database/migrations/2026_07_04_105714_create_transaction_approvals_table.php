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
        Schema::create('transaction_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approved_by')->constrained('users');
            $table->unsignedTinyInteger('level');
            $table->string('decision', 20);
            $table->string('comment')->nullable();
            $table->timestamps();

            // Un meme utilisateur ne peut pas enregistrer deux decisions
            // pour le meme niveau de la meme transaction (idempotence) :
            // protege contre un double-clic ou une double soumission.
            $table->unique(['transaction_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_approvals');
    }
};
