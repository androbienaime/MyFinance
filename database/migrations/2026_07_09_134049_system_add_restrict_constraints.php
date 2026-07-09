<?php
// database/migrations/xxxx_xx_xx_add_restrict_constraints.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->applyRestrict('customers', 'person_id', 'people');
        $this->applyRestrict('accounts', 'customer_id', 'customers');
        $this->applyRestrict('transactions', 'account_id', 'accounts');
    }

    public function down(): void
    {
        $this->applyCascade('transactions', 'account_id', 'accounts');
        $this->applyCascade('accounts', 'customer_id', 'customers');
        $this->applyCascade('customers', 'person_id', 'people');
    }

    /**
     * Supprime la contrainte existante (si présente) puis la recrée en RESTRICT.
     * Fonctionne que la contrainte existe déjà ou non, sur base fraîche ou existante.
     */
    private function applyRestrict(string $table, string $column, string $referencedTable): void
    {
        $this->dropForeignIfExists($table, $column);

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable) {
            $blueprint->foreign($column)
                ->references('id')->on($referencedTable)
                ->restrictOnDelete();
        });
    }

    /**
     * Symétrique de applyRestrict(), utilisé pour le rollback.
     */
    private function applyCascade(string $table, string $column, string $referencedTable): void
    {
        $this->dropForeignIfExists($table, $column);

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable) {
            $blueprint->foreign($column)
                ->references('id')->on($referencedTable)
                ->cascadeOnDelete();
        });
    }

    /**
     * Cherche le nom réel de la contrainte FK sur une colonne (peu importe
     * comment elle a été nommée à l'origine) et la supprime si trouvée.
     * Ne fait rien si aucune contrainte n'existe encore sur cette colonne.
     */
    private function dropForeignIfExists(string $table, string $column): void
    {
        $constraintName = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraintName) {
            Schema::table($table, function (Blueprint $blueprint) use ($constraintName) {
                $blueprint->dropForeign($constraintName);
            });
        }
    }
};