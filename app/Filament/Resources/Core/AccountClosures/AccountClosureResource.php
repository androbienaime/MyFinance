<?php

namespace App\Filament\Resources\Core\AccountClosures;

use App\Filament\Resources\Core\AccountClosures\Pages\CreateAccountClosure;
use App\Filament\Resources\Core\AccountClosures\Pages\EditAccountClosure;
use App\Filament\Resources\Core\AccountClosures\Pages\ListAccountClosures;
use App\Filament\Resources\Core\AccountClosures\Schemas\AccountClosureForm;
use App\Filament\Resources\Core\AccountClosures\Tables\AccountClosuresTable;
use App\Models\Core\AccountClosure;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AccountClosureResource extends Resource
{
    protected static ?string $model = AccountClosure::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Manage Accounts';
    protected static ?int $navigationSort = 3;



    public static function form(Schema $schema): Schema
    {
        return AccountClosureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountClosuresTable::configure($table);
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
            'index' => ListAccountClosures::route('/'),
            // 'create' => CreateAccountClosure::route('/create'),
            // 'edit' => EditAccountClosure::route('/{record}/edit'),
        ];
    }
}
