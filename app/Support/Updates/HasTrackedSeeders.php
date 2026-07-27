<?php

namespace App\Support\Updates;

use App\Models\Core\SystemUpdate;
use Illuminate\Support\Facades\DB;

trait HasTrackedSeeders
{
    /**
     * Appelle un seeder seulement s'il n'a pas déjà été appliqué.
     * La clé = nom de la classe du seeder (stable, explicite).
     */
    protected function callTracked(string $seederClass, ?string $key = null): void
    {
            \Log::info('callTracked appelé pour: ' . $seederClass); // <-- temporaire

        $key ??= $seederClass;

        if (SystemUpdate::where('update_key', $key)->exists()) {
            $this->command?->line("— Déjà appliqué : <comment>{$key}</comment>");
            return;
        }

        $this->command?->line("→ Application de : <comment>{$key}</comment>");

        DB::transaction(fn () => $this->call($seederClass));

        $description = "Description : '{$key}'" . class_basename($seederClass);

        SystemUpdate::create([
            'update_key' => $key,
            'description' => $description,
            'applied_at' => now(),
        ]);

        $this->command?->info("✅ {$key} appliqué et enregistré.");
    }
}