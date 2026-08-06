<?php

namespace App\Filament\Widgets;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Filament\Resources\Core\Reports\ReportResource;
use App\Models\Core\Report;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LatestReportsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    // protected string|null $heading = 'Derniers rapports';

    public static function canView(): bool
    {
        return Auth::user()->can('system.full-access') || Auth::user()->can('reports.manage');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->scopedQuery())
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (ReportType $state) => $state->label())
                    ->color(fn (ReportType $state) => $state->color()),
                TextColumn::make('category')
                    ->label('Categorie')
                    ->formatStateUsing(fn (ReportCategory $state) => $state->label()),
                TextColumn::make('title')->label('Titre')->limit(40),
                TextColumn::make('branch.name')->label('Succursale')->placeholder('Toutes'),
                TextColumn::make('employee.full_name')->label('Employe')->placeholder('Systeme'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ReportStatus $state) => $state->label())
                    ->color(fn (ReportStatus $state) => $state->color()),
                TextColumn::make('created_at')->label('Cree le')->dateTime(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Report $record) => ReportResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([5, 10, 25]);
    }

    protected function scopedQuery(): Builder
    {
        $user = Auth::user();
        $query = Report::query()
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        if ($user->isHeadOffice()) {
            return $query;
        }

        return $query->where('branch_id', $user->currentBranchId());
    }
}