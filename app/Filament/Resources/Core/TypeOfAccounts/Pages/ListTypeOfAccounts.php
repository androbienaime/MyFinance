<?php

namespace App\Filament\Resources\Core\TypeOfAccounts\Pages;

use App\Filament\Resources\Core\TypeOfAccounts\TypeOfAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTypeOfAccounts extends ListRecords
{
    protected static string $resource = TypeOfAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
