<?php

namespace App\Filament\Resources\Core\Currencies\Pages;

use App\Filament\Resources\Core\Currencies\CurrencyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCurrency extends CreateRecord
{
    protected static string $resource = CurrencyResource::class;
}
