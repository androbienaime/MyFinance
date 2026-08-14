<?php

namespace App\Filament\Resources\Core\QrPaymentFeeTiers\Pages;

use App\Filament\Resources\Core\QrPaymentFeeTiers\QrPaymentFeeTierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQrPaymentFeeTiers extends ListRecords
{
    protected static string $resource = QrPaymentFeeTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
