<?php

namespace App\Filament\Resources\Core\EarlyWithdrawalFees\Pages;

use App\Filament\Resources\Core\EarlyWithdrawalFees\EarlyWithdrawalFeeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEarlyWithdrawalFee extends EditRecord
{
    protected static string $resource = EarlyWithdrawalFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
