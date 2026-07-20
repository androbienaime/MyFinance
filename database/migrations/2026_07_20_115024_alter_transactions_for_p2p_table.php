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
        Schema::table('transactions', function (Blueprint $table) {
            // Un virement P2P n'a pas d'employe a l'origine - le client
            // agit seul depuis l'app.
            $table->foreignId('employee_id')->nullable()->change();
            $table->foreignId('initiated_by_customer_id')->nullable()->after('employee_id')
                ->constrained('customers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('initiated_by_customer_id');
            $table->foreignId('employee_id')->nullable(false)->change();
        });
    }
};
