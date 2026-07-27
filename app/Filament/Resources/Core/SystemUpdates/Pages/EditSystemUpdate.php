<?php

namespace App\Filament\Resources\Core\SystemUpdates\Pages;

use App\Filament\Resources\Core\SystemUpdates\SystemUpdateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSystemUpdate extends EditRecord
{
    protected static string $resource = SystemUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
