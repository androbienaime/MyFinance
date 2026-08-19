<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MyFinanceUpdate extends Command
{
    protected $signature = 'myfinance:update {--force : Skip confirmation}';
    protected $description = 'Applique les mises à jour du système (migrations, rôles, settings)';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirmToProceed()) {
            return self::FAILURE;
        }

        try {
            $this->info('🔄 Mise à jour de MyFinance...');

            $this->components->task('Migrations', fn () =>
                Artisan::call('migrate', ['--force' => true]) === 0
            );

            $this->components->task('Custom Seeder', fn () =>
                $this->call('db:seed', ['--class' => \Database\Seeders\CustomSeeder::class, '--force' => true]) === 0
            );

            $this->components->task('Rôles & permissions', fn () =>
                $this->call('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class, '--force' => true]) === 0
            );

            $this->components->task('Sync settings (config → DB)', fn () =>
                $this->call('myfinance:sync-settings') === 0
            );

            $this->components->task('Cache permissions', fn () =>
                Artisan::call('permission:cache-reset') === 0
            );

            $this->components->task('Cache config/routes/views', function () {
                Artisan::call('optimize:clear');
                return true;
            });

            $this->info('✅ MyFinance mis à jour avec succès.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Échec de la mise à jour : ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            // Garantit que le verrou est toujours retire, meme en cas
            // d'exception - sans ca, un echec de migration laisserait le
            // bouton Filament bloque indefiniment sur "en cours".
            @unlink(storage_path('logs/myfinance-update.lock'));
        }
    }

    protected function confirmToProceed(): bool
    {
        return $this->confirm('Lancer la mise à jour sur l\'environnement actuel ?');
    }
}