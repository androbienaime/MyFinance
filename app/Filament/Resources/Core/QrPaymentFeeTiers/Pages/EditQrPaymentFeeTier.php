<?php

namespace App\Filament\Resources\Core\QrPaymentFeeTiers\Pages;

use App\Filament\Resources\Core\QrPaymentFeeTiers\QrPaymentFeeTierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQrPaymentFeeTier extends EditRecord
{
    protected static string $resource = QrPaymentFeeTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
