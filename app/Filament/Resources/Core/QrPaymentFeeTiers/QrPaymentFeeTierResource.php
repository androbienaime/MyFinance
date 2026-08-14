<?php

namespace App\Filament\Resources\Core\QrPaymentFeeTiers;

use App\Filament\Resources\Core\QrPaymentFeeTiers\Schemas\QrPaymentFeeTierForm;
use App\Filament\Resources\Core\QrPaymentFeeTiers\Tables\QrPaymentFeeTiersTable;
use App\Models\Core\QrPaymentFeeTier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class QrPaymentFeeTierResource extends Resource
{
    protected static ?string $model = QrPaymentFeeTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::QrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $modelLabel = 'Palier de frais QR';

    protected static ?string $pluralModelLabel = 'Paliers de frais QR';

    public static function form(Schema $schema): Schema
    {
        return QrPaymentFeeTierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QrPaymentFeeTiersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQrPaymentFeeTiers::route('/'),
            // 'create' => Pages\CreateQrPaymentFeeTier::route('/create'),
            // 'edit' => Pages\EditQrPaymentFeeTier::route('/{record}/edit'),
        ];
    }
}