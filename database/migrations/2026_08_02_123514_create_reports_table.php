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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
 
            // 'automatic' = genere par le systeme (cron), 'manual' = redige par un employe
            $table->string('type', 20);
 
            // ex: daily_closing, monthly_summary, incident, cash_discrepancy, custom ...
            $table->string('category', 40);
 
            $table->string('title');
            $table->text('content')->nullable(); // redaction libre (rapports manuels)
            $table->json('data')->nullable(); // snapshot chiffre (rapports automatiques)
 
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
 
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
 
            $table->string('status', 20)->default('pending'); // pending, reviewed, archived
 
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
 
            $table->softDeletes();
            $table->timestamps();
 
            $table->index(['type', 'category']);
            $table->index(['branch_id', 'created_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
