<?php

namespace App\Filament\Resources\Core\P2pTransferFeeTiers\Pages;

use App\Filament\Resources\Core\P2pTransferFeeTiers\P2pTransferFeeTierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListP2pTransferFeeTiers extends ListRecords
{
    protected static string $resource = P2pTransferFeeTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
