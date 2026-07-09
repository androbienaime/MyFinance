<?php
// database/migrations/xxxx_xx_xx_create_employee_succursale_histories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_branch_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete(); // l'historique suit l'employé, cohérent avec ta règle de suppression

            $table->foreignId('branch_id')
                ->constrained()
                ->restrictOnDelete(); // on ne supprime jamais une succursale ayant un historique

            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable(); // NULL = affectation en cours

            $table->string('reason')->nullable(); // 'embauche', 'transfert', 'promotion', etc.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['employee_id', 'started_at', 'ended_at']);
            $table->index(['branch_id', 'started_at', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_branch_histories');
    }
};