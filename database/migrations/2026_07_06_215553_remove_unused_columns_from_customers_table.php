<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['address_id']); // si address_id est une clé étrangère

            $table->dropColumn([
                'firstname',
                'name',
                'gender',
                'identity_number',
                'address_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('firstname');
            $table->string('name');
            $table->string('gender');
            $table->string('identity_number');
            $table->foreignId('address_id')->nullable()->constrained();
        });
    }
};