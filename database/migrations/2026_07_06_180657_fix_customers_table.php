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
            $table->foreignId('person_id')->nullable();
            $table->string('email')->nullable()->unique();            
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('accounts_person_id_foreign');
            $table->dropColumn('person_id');
            $table->dropColumn('email');
            $table->dropColumn('email_verified_at');
            $table->dropColumn('password');
        });


    }
};
