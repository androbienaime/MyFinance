<?php

namespace App\Filament\Resources\Core\MerchantApiKeys\Pages;

use App\Filament\Resources\Core\MerchantApiKeys\MerchantApiKeyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMerchantApiKey extends EditRecord
{
    protected static string $resource = MerchantApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
