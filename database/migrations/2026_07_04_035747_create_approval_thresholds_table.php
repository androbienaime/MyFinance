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
        Schema::create('approval_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->decimal('min_amount', 14, 2);
            $table->unsignedTinyInteger('required_levels')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'min_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_thresholds');
    }
};
