<?php

namespace App\Filament\Resources\Core\P2pTransferRequests\Pages;

use App\Filament\Resources\Core\P2pTransferRequests\P2pTransferRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListP2pTransferRequests extends ListRecords
{
    protected static string $resource = P2pTransferRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
