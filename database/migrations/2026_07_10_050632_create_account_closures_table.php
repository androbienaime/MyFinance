<?php
// database/migrations/xxxx_xx_xx_create_account_closures_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('is_active');
        });

        Schema::create('account_closures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')
                ->constrained()
                ->cascadeOnDelete();

            // 'manual' = fermeture volontaire, 'settlement' = via règlement de solde
            $table->string('type');

            $table->string('reason')->nullable(); // non obligatoire, comme demandé

            $table->decimal('balance_at_closure', 15, 2)->default(0);

            $table->foreignId('closed_by')->nullable()->constrained('employees')->nullOnDelete();

            $table->timestamps();

            $table->index(['account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_closures');

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });
    }
};