<?php

namespace App\Filament\Resources\Core\CaisseSessions\Pages;

use App\Actions\OpenCaisseSessionAction;
use App\Enums\CaisseSessionStatus;
use App\Exceptions\CaisseSessionException;
use App\Filament\Resources\Core\CaisseSessions\CaisseSessionsResource;
use App\Models\Core\CaisseSession;
use App\Models\Core\Currency;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;

class ListCaisseSessions extends ListRecords
{
    protected static string $resource = CaisseSessionsResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes'),
            'open' => Tab::make('Ouvertes')
                ->badge(fn () => CaisseSessionsResource::getEloquentQuery()->where('status', CaisseSessionStatus::Open->value)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn ($query) => $query->where('status', CaisseSessionStatus::Open->value)),
            'closed' => Tab::make('Fermees')
                ->modifyQueryUsing(fn ($query) => $query->where('status', CaisseSessionStatus::Closed->value)),
            'discrepancy' => Tab::make('Avec ecart')
                ->badge(fn () => CaisseSessionsResource::getEloquentQuery()
                    ->whereHas('balances', fn ($q) => $q->whereNotNull('closing_discrepancy')->where('closing_discrepancy', '!=', 0))
                    ->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn ($query) => $query->whereHas(
                    'balances',
                    fn ($q) => $q->whereNotNull('closing_discrepancy')->where('closing_discrepancy', '!=', 0)
                )),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')
                ->label('Ouvrir ma caisse')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->visible(function () {
                    $user = auth()->user();

                    if (! $user->can('open', CaisseSession::class)) {
                        return false;
                    }

                    return ! CaisseSession::query()
                        ->where('employee_id', $user->employee?->id)
                        ->forDate(now())
                        ->exists();
                })
                ->schema(fn () => [
                    Grid::make(2)->schema(
                        Currency::where('is_active', true)
                        ->get()
                        ->map(fn (Currency $currency) => TextInput::make("declared.{$currency->id}")
                            ->label("Montant compte ({$currency->iso_code})")
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                        )
                        ->all()
                    ),
                ])
                ->action(function (array $data) {
                    try {
                        app(OpenCaisseSessionAction::class)->handle(
                            auth()->user()->employee,
                            collect($data['declared'] ?? [])->map(fn ($v) => (float) $v)->all(),
                        );

                        Notification::make()->title('Caisse ouverte')->success()->send();
                    } catch (CaisseSessionException $e) {
                        Notification::make()
                            ->title('Impossible d\'ouvrir la caisse')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}