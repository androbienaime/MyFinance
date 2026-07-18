<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;

class InstallCommand extends Command
{
    protected $signature = 'myfinance:install
        {--fresh : Reinitialise completement la base (migrate:fresh) au lieu de migrate}
        {--skip-user : Ne pas creer d\'utilisateur a la fin de l\'installation}';

    protected $description = 'Installation complete de MyFinance : cle app, migrations, seeders, assets Filament, et premier utilisateur';

    public function handle(): int
    {
        $this->components->info('Installation de MyFinance');
        $this->ensureEnvFile();
        $this->ensureAppKey();
        $this->call('optimize'); // seulement une fois la clé garantie
        $this->runMigrations();
        $this->runSeeders();
        $this->publishFilamentAssets();
        
        if (! $this->option('skip-user')) {
            $this->createFirstUser();
        }

        $this->newLine();
        $this->components->info('Installation terminee.');
        note('Lancez `php artisan serve` puis connectez-vous sur /admin.');

        return self::SUCCESS;
    }

    private function ensureEnvFile(): void
    {
        if (File::exists(base_path('.env'))) {
            return;
        }

        $this->components->task('Creation du fichier .env', function () {
            File::copy(base_path('.env.example'), base_path('.env'));

            return true;
        });
    }

    private function ensureAppKey(): void
    {
        if (! blank(config('app.key'))) {
            return;
        }

        $this->components->task('Generation de la cle applicative', function () {
            Artisan::call('key:generate', ['--force' => true]);

            return true;
        });
    }

    private function runMigrations(): void
    {
        $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';

        $this->components->task("Execution des migrations ({$command})", function () use ($command) {
            Artisan::call($command, ['--force' => true]);

            return true;
        });
    }

    private function runSeeders(): void
    {
        $this->components->task('Seed des roles, permissions et donnees de reference', function () {
            Artisan::call('db:seed', ['--force' => true]);

            return true;
        });
    }

    private function publishFilamentAssets(): void
    {
        $this->components->task('Publication des assets Filament', function () {
            Artisan::call('filament:assets');

            return true;
        });
    }

    private function createFirstUser(): void
    {
        if (! confirm('Creer le premier utilisateur (siege) maintenant ?', default: true)) {
            return;
        }

        // Volontairement PAS de $this->call('myfinance:make-user') ici :
        // les prompts interactifs (text/password/select) ne s'affichent pas
        // de facon fiable quand une commande en appelle une autre via call(),
        // l'entree/sortie du terminal n'etant pas correctement transmise a
        // la sous-commande. On demande donc explicitement a l'utilisateur de
        // lancer la commande lui-meme, juste apres.
        $this->newLine();
        $this->components->warn('Lancez maintenant la commande suivante pour creer votre utilisateur :');
        $this->line('    php artisan myfinance:make-user');
    }
}