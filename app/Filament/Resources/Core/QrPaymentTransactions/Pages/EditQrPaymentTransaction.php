<?php

namespace App\Filament\Resources\Core\QrPaymentTransactions\Pages;

use App\Filament\Resources\Core\QrPaymentTransactions\QrPaymentTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQrPaymentTransaction extends EditRecord
{
    protected static string $resource = QrPaymentTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
