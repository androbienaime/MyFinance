<?php

namespace App\Filament\Resources\Core\QrPaymentTransactions\Pages;

use App\Filament\Resources\Core\QrPaymentTransactions\QrPaymentTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQrPaymentTransaction extends CreateRecord
{
    protected static string $resource = QrPaymentTransactionResource::class;
}
