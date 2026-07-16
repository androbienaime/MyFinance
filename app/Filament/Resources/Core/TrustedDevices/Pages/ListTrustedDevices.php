<?php

namespace App\Filament\Resources\Core\TrustedDevices\Pages;

use App\Filament\Resources\Core\TrustedDevices\TrustedDeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrustedDevices extends ListRecords
{
    protected static string $resource = TrustedDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
