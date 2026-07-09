<?php
// app/Filament/Actions/GuardedDeleteBulkAction.php

namespace App\Filament\Actions;

use App\Contracts\Deletable;
use Filament\Actions\DeleteBulkAction;
// use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class GuardedDeleteBulkAction extends DeleteBulkAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->action(function (Collection $records): void {
            [$deletable, $blocked] = $records->partition(
                fn ($record) => ! ($record instanceof Deletable) || ! $record->isDeletionBlocked()
            );

            $deletable->each->delete();

            $this->sendFeedback($deletable->count(), $blocked);
        });
    }

    protected function sendFeedback(int $deletedCount, Collection $blocked): void
    {
        if ($blocked->isEmpty()) {
            Notification::make()
                ->success()
                ->title("{$deletedCount} élément(s) supprimé(s)")
                ->send();

            return;
        }

        Notification::make()
            ->warning()
            ->title('Suppression partielle')
            ->body(sprintf(
                '%d supprimé(s), %d protégé(s) : %s',
                $deletedCount,
                $blocked->count(),
                $blocked->map->getDeletionGuardMessage()->unique()->implode(' ')
            ))
            ->send();
    }
}