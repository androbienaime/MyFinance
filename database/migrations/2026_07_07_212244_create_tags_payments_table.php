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
        Schema::create('tags_payments', function (Blueprint $table) {
            $table->id();
            $table->integer("tags")->nullable(false);
            $table->foreignId("account_id")->constrained();
            $table->foreignId("transaction_id")->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags_payments');
    }
};
