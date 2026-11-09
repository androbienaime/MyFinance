<?php

namespace App\Filament\Resources\Core\Customers\Pages;

use App\Filament\Actions\GuardedDeleteAction;
use App\Filament\Resources\Core\Customers\CustomerResource;
use App\Filament\Resources\Core\Customers\Tables\Actions\CustomerStatementActions;
use App\Filament\Resources\Core\Customers\Tables\Actions\EnableCustomerOnlineAccessAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            EnableCustomerOnlineAccessAction::make(),
            GuardedDeleteAction::make(),
            CustomerStatementActions::download(),
            CustomerStatementActions::print(),
        ];
    }
}
