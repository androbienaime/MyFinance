<?php

namespace App\Filament\Resources\Core\MerchantProfiles\Pages;

use App\Filament\Resources\Core\MerchantProfiles\MerchantProfilesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMerchantProfiles extends ListRecords
{
    protected static string $resource = MerchantProfilesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
