<?php

namespace App\Filament\Resources\Core\QrPaymentTransactions\Pages;

use App\Filament\Resources\Core\QrPaymentTransactions\QrPaymentTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQrPaymentTransactions extends ListRecords
{
    protected static string $resource = QrPaymentTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
