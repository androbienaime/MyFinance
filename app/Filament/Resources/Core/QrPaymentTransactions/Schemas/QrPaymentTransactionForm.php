<?php

namespace App\Filament\Resources\Core\QrPaymentTransactions\Schemas;

use Filament\Schemas\Schema;

class QrPaymentTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
