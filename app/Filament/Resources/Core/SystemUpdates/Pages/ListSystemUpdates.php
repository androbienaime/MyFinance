<?php

namespace App\Filament\Resources\Core\SystemUpdates\Pages;

use App\Filament\Resources\Core\SystemUpdates\SystemUpdateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSystemUpdates extends ListRecords
{
    protected static string $resource = SystemUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
