<?php
// app/Filament/Actions/GuardedDeleteAction.php

namespace App\Filament\Actions;

use App\Contracts\Deletable;
use Filament\Actions\DeleteAction;
// use Filament\Tables\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class GuardedDeleteAction extends DeleteAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->disabled(fn (Model $record) => $record instanceof Deletable
            && $record->isDeletionBlocked());

        $this->tooltip(fn (Model $record) => $record instanceof Deletable && $record->isDeletionBlocked()
            ? $record->getDeletionGuardMessage()
            : null);

        $this->before(function (Model $record, self $action) {
            if ($record instanceof Deletable && $record->isDeletionBlocked()) {
                Notification::make()
                    ->danger()
                    ->title('Suppression impossible')
                    ->body($record->getDeletionGuardMessage())
                    ->send();

                $action->cancel();
            }
        });
    }
}