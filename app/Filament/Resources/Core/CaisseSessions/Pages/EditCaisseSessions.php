<?php

namespace App\Filament\Resources\Core\CaisseSessions\Pages;

use App\Filament\Resources\Core\CaisseSessions\CaisseSessionsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCaisseSessions extends EditRecord
{
    protected static string $resource = CaisseSessionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
