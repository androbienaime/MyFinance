<?php

namespace App\Filament\Resources\Core\MerchantApiKeys\Pages;

use App\Filament\Resources\Core\MerchantApiKeys\MerchantApiKeyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMerchantApiKey extends CreateRecord
{
    protected static string $resource = MerchantApiKeyResource::class;
}
