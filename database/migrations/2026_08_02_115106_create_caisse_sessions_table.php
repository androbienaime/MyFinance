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
        Schema::create('caisse_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('session_date');

            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();

            // open, closed
            $table->string('status', 20)->default('open');

            $table->timestamps();

            // Un seul caissier ne peut avoir qu'une session (un shift) par jour
            $table->unique(['employee_id', 'session_date']);
            $table->index(['branch_id', 'session_date']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_sessions');
    }
};
