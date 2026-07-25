<?php

namespace App\Filament\Resources\Core\P2pTransferFeeTiers\Pages;

use App\Filament\Resources\Core\P2pTransferFeeTiers\P2pTransferFeeTierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditP2pTransferFeeTier extends EditRecord
{
    protected static string $resource = P2pTransferFeeTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
