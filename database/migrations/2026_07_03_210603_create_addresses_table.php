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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId("country_id")->nullable();
            $table->foreignId("state_id")->nullable();
            $table->foreignId("city_id")->nullable();
            $table->string("city2")->nullable(true);
            $table->string("phone")->nullable(true);
            $table->integer("phone_code")->nullable(true);
            $table->string("email")->nullable(true);
            $table->string("address1")->nullable(true);
            $table->string("address2")->nullable(true);
            $table->string("alias")->nullable(true);
            $table->string("company")->nullable(true);
            $table->boolean("active")->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
