<?php

namespace App\Filament\Resources\Core\P2pTransferRequests\Pages;

use App\Filament\Resources\Core\P2pTransferRequests\P2pTransferRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditP2pTransferRequest extends EditRecord
{
    protected static string $resource = P2pTransferRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
