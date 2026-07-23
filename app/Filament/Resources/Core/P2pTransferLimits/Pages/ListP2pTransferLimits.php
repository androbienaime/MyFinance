<?php

namespace App\Filament\Resources\Core\P2pTransferLimits\Pages;

use App\Filament\Resources\Core\P2pTransferLimits\P2pTransferLimitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListP2pTransferLimits extends ListRecords
{
    protected static string $resource = P2pTransferLimitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
