<?php

namespace App\Filament\Resources\Core\MerchantApiKeys\Pages;

use App\Filament\Resources\Core\MerchantApiKeys\MerchantApiKeyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMerchantApiKeys extends ListRecords
{
    protected static string $resource = MerchantApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
