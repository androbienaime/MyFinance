<?php

namespace App\Filament\Resources\Core\EarlyWithdrawalFees;

use App\Filament\Resources\Core\EarlyWithdrawalFees\Pages\CreateEarlyWithdrawalFee;
use App\Filament\Resources\Core\EarlyWithdrawalFees\Pages\EditEarlyWithdrawalFee;
use App\Filament\Resources\Core\EarlyWithdrawalFees\Pages\ListEarlyWithdrawalFees;
use App\Filament\Resources\Core\EarlyWithdrawalFees\Schemas\EarlyWithdrawalFeeForm;
use App\Filament\Resources\Core\EarlyWithdrawalFees\Tables\EarlyWithdrawalFeesTable;
use App\Models\Core\EarlyWithdrawalFee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EarlyWithdrawalFeeResource extends Resource
{
    protected static ?string $model = EarlyWithdrawalFee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Administration';


    public static function getNavigationLabel(): string
    {
        return __('myfinance.early_withdrawal_fees');
    }

    public static function form(Schema $schema): Schema
    {
        return EarlyWithdrawalFeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EarlyWithdrawalFeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEarlyWithdrawalFees::route('/'),
            'create' => CreateEarlyWithdrawalFee::route('/create'),
            'edit' => EditEarlyWithdrawalFee::route('/{record}/edit'),
        ];
    }
}
