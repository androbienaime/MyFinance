<?php

namespace App\Filament\Resources\Core\LoginAttempts\Pages;

use App\Filament\Resources\Core\LoginAttempts\LoginAttemptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoginAttempt extends EditRecord
{
    protected static string $resource = LoginAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
