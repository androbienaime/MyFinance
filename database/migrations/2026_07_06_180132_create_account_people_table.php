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
        Schema::create('account_people', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('person_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('role'); 
            // owner, co_owner, attorney, beneficiary, guardian

            $table->json('permissions')->nullable();
            // ex: ["withdraw", "deposit", "view"]

            $table->decimal('share_percentage', 5, 2)->nullable();
            // utile pour bénéficiaires (ex: 50%)

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->boolean('is_active')->default(true);


            $table->unique(['account_id', 'person_id', 'role']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_people');
    }
};
