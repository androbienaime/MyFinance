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
            // Rendre le mot de passe nullable
            $table->string('password')->nullable()->change();

            // Ajouter le statut de connexion
            $table->boolean('is_online')->default(false)->after('password');
            $table->boolean('is_active')->default(true)->after('is_online');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();

            $table->dropColumn('is_online');
            $table->dropColumn('is_active');
        });
    }
};