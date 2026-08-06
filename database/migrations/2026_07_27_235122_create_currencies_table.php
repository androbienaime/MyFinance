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
   Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("symbol");
            $table->string("iso_code")->unique();
            $table->string("country");
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->decimal('exchange_rate', 20, 6)->default(1); 
            $table->boolean("is_active")->default(1);
            $table->timestamps();
        });

        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained()->onDelete('cascade');
            $table->decimal('rate', 20, 6);
            $table->date('date');
            $table->timestamps();

            $table->unique(['currency_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('currency_rates');

    }
};
