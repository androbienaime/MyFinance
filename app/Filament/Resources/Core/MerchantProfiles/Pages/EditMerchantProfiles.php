<?php

namespace App\Filament\Resources\Core\MerchantProfiles\Pages;

use App\Filament\Resources\Core\MerchantProfiles\MerchantProfilesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMerchantProfiles extends EditRecord
{
    protected static string $resource = MerchantProfilesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
