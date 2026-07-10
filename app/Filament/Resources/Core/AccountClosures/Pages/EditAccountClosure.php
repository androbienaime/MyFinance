<?php

namespace App\Filament\Resources\Core\AccountClosures\Pages;

use App\Filament\Resources\Core\AccountClosures\AccountClosureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAccountClosure extends EditRecord
{
    protected static string $resource = AccountClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
