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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->foreignId('branch_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->json('value')->nullable();
            $table->foreignId('updated_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['key', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
