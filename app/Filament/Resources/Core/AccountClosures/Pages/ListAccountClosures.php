<?php

namespace App\Filament\Resources\Core\AccountClosures\Pages;

use App\Filament\Resources\Core\AccountClosures\AccountClosureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccountClosures extends ListRecords
{
    protected static string $resource = AccountClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
