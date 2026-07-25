<?php

namespace App\Filament\Resources\Core\P2pTransferLimits\Pages;

use App\Filament\Resources\Core\P2pTransferLimits\P2pTransferLimitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditP2pTransferLimit extends EditRecord
{
    protected static string $resource = P2pTransferLimitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
