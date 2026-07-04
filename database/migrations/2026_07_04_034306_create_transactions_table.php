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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained();
            $table->string('code')->unique();
            $table->decimal('amount', 14, 2);
            $table->foreignId('employee_id')->constrained();
            $table->string('type', 20);
            $table->string('status', 20)->default('pending');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['account_id', 'created_at']);
            $table->index(['employee_id', 'created_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
