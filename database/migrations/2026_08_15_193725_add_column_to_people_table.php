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
        Schema::table('people', function (Blueprint $table) {
            $table->string("date_of_birth")->nullable()->after("gender");
            $table->string("place_of_birth")->nullable()->after("date_of_birth");
            $table->string("nationality")->nullable()->after("place_of_birth");
            $table->string("marital_status")->nullable()->after("nationality");
            $table->string("occupation")->nullable()->after("marital_status");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['date_of_birth', 'place_of_birth', 'nationality', 'marital_status', 'occupation']);
        });
    }
};
