<?php

namespace App\Filament\Resources\Core\TypeOfAccounts\Pages;

use App\Filament\Resources\Core\TypeOfAccounts\TypeOfAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTypeOfAccount extends EditRecord
{
    protected static string $resource = TypeOfAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
