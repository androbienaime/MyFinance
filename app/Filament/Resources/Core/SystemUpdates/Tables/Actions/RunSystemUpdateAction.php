<?php

// app/Filament/Resources/Core/SystemUpdates/Tables/Actions/RunSystemUpdateAction.php
namespace App\Filament\Resources\Core\SystemUpdates\Tables\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;

class RunSystemUpdateAction
{
    public static function make(): Action
    {
        return Action::make('runUpdate')
            ->label('Lancer myfinance:update')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn () => auth()->user()->can('system_updates.run'))
            ->requiresConfirmation()
            ->modalHeading('Lancer la mise à jour du système')
            ->modalDescription('Cette commande exécute les migrations et seeders en attente. Elle peut prendre plusieurs minutes.')
            ->modalSubmitActionLabel('Lancer maintenant')
            ->action(function () {
                $logPath = storage_path('logs/myfinance-update.log');
                $lockPath = storage_path('logs/myfinance-update.lock');

                if (file_exists($lockPath)) {
                    Notification::make()->title('Une mise à jour est déjà en cours.')->warning()->send();
                    return;
                }

                file_put_contents($lockPath, (string) now());
                file_put_contents($logPath, "=== Démarrage : " . now()->toDateTimeString() . " ===\n");

                $phpBinary = (new \Symfony\Component\Process\PhpExecutableFinder())->find() ?: 'php';
                $artisanPath = base_path('artisan');

                // Descripteurs de type FILE (pas 'pipe') : le systeme
                // d'exploitation ecrit directement dans le fichier,
                // independamment du parent - contrairement a un pipe qui
                // exige que le process appelant reste vivant pour relayer
                // la sortie. C'est ce qui manquait dans la version
                // precedente base sur Process::start() + callback.
                $descriptorSpec = [
                    0 => ['pipe', 'r'],
                    1 => ['file', $logPath, 'a'],
                    2 => ['file', $logPath, 'a'],
                ];

                $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($artisanPath)
                    . ' myfinance:update --force';

                $process = proc_open($command, $descriptorSpec, $pipes, base_path());

                if (! is_resource($process)) {
                    @unlink($lockPath);

                    Notification::make()
                        ->title('Impossible de démarrer la mise à jour.')
                        ->danger()
                        ->send();

                    return;
                }

                // On ferme stdin immediatement (rien a y ecrire) et on NE
                // PAS appeler proc_close() - ca bloquerait jusqu'a la fin
                // du process, ce qu'on veut justement eviter ici.
                fclose($pipes[0]);

                Notification::make()
                    ->title('Mise à jour lancée en arrière-plan.')
                    ->body('Suivez la progression via le bouton "Voir la console".')
                    ->success()
                    ->send();
            });
    }

    public static function viewConsole(): Action
    {
        return Action::make('viewUpdateConsole')
            ->label('Voir la console')
            ->icon('heroicon-o-command-line')
            ->color('gray')
            ->modalHeading('Sortie de myfinance:update')
            ->modalWidth('4xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fermer')
            ->schema([
                \Filament\Forms\Components\ViewField::make('console_output')
                    ->view('filament.components.update-console')
                    ->dehydrated(false),
            ]);
    }
}