<?php

namespace App\Filament\Resources\Core\TrustedDevices\Pages;

use App\Filament\Resources\Core\TrustedDevices\TrustedDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrustedDevice extends EditRecord
{
    protected static string $resource = TrustedDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
