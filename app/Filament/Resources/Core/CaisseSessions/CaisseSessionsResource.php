<?php

namespace App\Filament\Resources\Core\CaisseSessions;

use App\Filament\Resources\Core\CaisseSessions\Pages\ListCaisseSessions;
use App\Filament\Resources\Core\CaisseSessions\Pages\ViewCaisseSession;
use App\Filament\Resources\Core\CaisseSessions\Schemas\CaisseSessionInfolist;
use App\Filament\Resources\Core\CaisseSessions\Tables\CaisseSessionsTable;
use App\Models\Core\CaisseSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CaisseSessionsResource extends Resource
{
    protected static ?string $model = CaisseSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string
    {
        return __('myfinance.reports');
    }

    public static function getNavigationLabel(): string
    {
        return 'Caisses';
    }

    public static function infolist(Schema $schema): Schema
    {
        return CaisseSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CaisseSessionsTable::configure($table);
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
            'index' => ListCaisseSessions::route('/'),
            // 'view' => ViewCaisseSession::route('/{record}'),
        ];
    }

    /**
     * Le siege voit toutes les caisses. Un superviseur/admin (caisse.view)
     * voit celles de sa propre succursale. Un simple caissier ne voit que
     * ses propres sessions.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || $user->isHeadOffice()) {
            return $query;
        }

        if ($user->can('caisse.view')) {
            return $query->where('branch_id', $user->currentBranchId());
        }

        return $query->where('employee_id', $user->employee?->id);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()
            ->where('status', \App\Enums\CaisseSessionStatus::Open->value)
            ->count() ?: null;
    }
}