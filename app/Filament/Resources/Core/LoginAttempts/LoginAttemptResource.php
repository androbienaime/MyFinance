<?php

namespace App\Filament\Resources\Core\LoginAttempts;

use App\Filament\Resources\Core\LoginAttempts\Pages\CreateLoginAttempt;
use App\Filament\Resources\Core\LoginAttempts\Pages\EditLoginAttempt;
use App\Filament\Resources\Core\LoginAttempts\Pages\ListLoginAttempts;
use App\Filament\Resources\Core\LoginAttempts\Schemas\LoginAttemptForm;
use App\Filament\Resources\Core\LoginAttempts\Tables\LoginAttemptsTable;
use App\Models\Core\LoginAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LoginAttemptResource extends Resource
{
    protected static ?string $model = LoginAttempt::class;


    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserMinus;

    protected static string|UnitEnum|null $navigationGroup = 'Sécurité';

    protected static ?string $navigationLabel = 'Login Attempt';

    protected static ?string $modelLabel = 'Appareil';

    protected static ?string $pluralModelLabel = 'Appareils connus';

    public static function canCreate(): bool
    {
        return false; // lecture seule + actions, pas de création manuelle
    }

    public static function table(Table $table): Table
    {
        return LoginAttemptsTable::configure($table);
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
            'index' => ListLoginAttempts::route('/'),
            'create' => CreateLoginAttempt::route('/create'),
            'edit' => EditLoginAttempt::route('/{record}/edit'),
        ];
    }
}
