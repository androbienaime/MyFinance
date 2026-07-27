<?php

namespace App\Filament\Resources\Core\EarlyWithdrawalFees\Pages;

use App\Filament\Resources\Core\EarlyWithdrawalFees\EarlyWithdrawalFeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEarlyWithdrawalFees extends ListRecords
{
    protected static string $resource = EarlyWithdrawalFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
