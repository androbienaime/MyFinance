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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('firstname');
            $table->string('gender')->nullable();
            $table->string('identity_number')->nullable()->unique();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('address_id')->nullable()->constrained();
            $table->softDeletes();
            $table->timestamps();

            // Index compose pour accelerer le tri par pertinence
            // (scopeOrderByRelevanceTo) une fois qu'il y aura du volume.
            $table->index(['employee_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
