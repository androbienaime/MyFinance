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
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('customer_id');

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign('accounts_employee_id_foreign');
            $table->dropColumn('employee_id');
        });
    }
};
