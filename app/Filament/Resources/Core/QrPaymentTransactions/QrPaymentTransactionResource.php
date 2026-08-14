<?php

namespace App\Filament\Resources\Core\QrPaymentTransactions;

use App\Filament\Resources\Core\QrPaymentTransactions\Tables\QrPaymentTransactionsTable;
use App\Models\Core\Transaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use Illuminate\Database\Eloquent\Builder;


class QrPaymentTransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::QrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?string $modelLabel = 'Transaction QR';

    protected static ?string $pluralModelLabel = 'Transactions QR';

    public static ?int $navigationSort = 5;


    public static function getNavigationGroup(): string
    {
        return __('myfinance.operations');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('qr_payment_transactions.view_any') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('qr_payment_transactions.view_any') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('qr_payment_transactions.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Ne cible QUE les types lies aux paiements QR - independant du
     * scope base sur employee_id, puisque ces transactions sont
     * toujours initiees par un customer (employee_id = null).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('type', [
                \App\Enums\TransactionType::QrPayment->value,
                \App\Enums\TransactionType::QrPaymentFee->value,
            ])
            ->where('status', \App\Enums\TransactionStatus::Completed->value);
    }

    public static function table(Table $table): Table
    {
        return QrPaymentTransactionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQrPaymentTransactions::route('/'),
        ];
    }
}