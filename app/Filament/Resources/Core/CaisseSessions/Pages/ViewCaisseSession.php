<?php

namespace App\Filament\Resources\Core\CaisseSessions\Pages;

use App\Actions\CloseCaisseSessionAction;
use App\Exceptions\CaisseSessionException;
use App\Filament\Resources\Core\CaisseSessions\CaisseSessionsResource;
use App\Models\Core\CaisseSession;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ViewCaisseSession extends ViewRecord
{
    protected static string $resource = CaisseSessionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('close')
                ->label('Fermer ma caisse')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->visible(fn (CaisseSession $record) => auth()->user()->can('close', $record))
                ->requiresConfirmation()
                ->schema(fn (CaisseSession $record) => [
                    Grid::make(1)->schema(
                        $record->balances->map(fn ($balance) => Section::make($balance->currency->code)
                            ->schema([
                                TextInput::make("closing.{$balance->currency_id}.declared")
                                    ->label('Montant compte en caisse')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                                Textarea::make("closing.{$balance->currency_id}.comment")
                                    ->label('Commentaire (obligatoire en cas d\'ecart)')
                                    ->rows(2),
                            ]))->all()
                    ),
                ])
                ->action(function (CaisseSession $record, array $data) {
                    try {
                        app(CloseCaisseSessionAction::class)->handle($record, $data['closing'] ?? []);

                        Notification::make()->title('Caisse fermee')->success()->send();
                    } catch (CaisseSessionException $e) {
                        Notification::make()
                            ->title('Impossible de fermer la caisse')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('forceClose')
                ->label('Fermeture forcee')
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')
                ->visible(fn (CaisseSession $record) => auth()->user()->can('forceClose', $record))
                ->requiresConfirmation()
                ->modalDescription('A utiliser uniquement si le caissier ne peut pas fermer lui-meme sa caisse.')
                ->schema(fn (CaisseSession $record) => [
                    Grid::make(1)->schema(
                        $record->balances->map(fn ($balance) => Section::make($balance->currency->code)
                            ->schema([
                                TextInput::make("closing.{$balance->currency_id}.declared")
                                    ->label('Montant compte (verifie par vous)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                                Textarea::make("closing.{$balance->currency_id}.comment")
                                    ->label('Raison de la fermeture forcee')
                                    ->required()
                                    ->rows(2),
                            ]))->all()
                    ),
                ])
                ->action(function (CaisseSession $record, array $data) {
                    try {
                        app(CloseCaisseSessionAction::class)->handle($record, $data['closing'] ?? []);

                        Notification::make()->title('Caisse fermee de force')->warning()->send();
                    } catch (CaisseSessionException $e) {
                        Notification::make()
                            ->title('Impossible de fermer la caisse')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}