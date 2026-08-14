<?php

namespace App\Filament\Resources\Core\MerchantApiKeys\Tables;

use App\Actions\RevokeMerchantApiKeyAction;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\MerchantApiKey;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MerchantApiKeysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('merchantProfile.business_name')
                    ->label('Marchand')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('merchantProfile.account.code')
                    ->label('Compte'),

                TextColumn::make('name')
                    ->label('Nom de la cle')
                    ->searchable(),

                TextColumn::make('key_prefix')
                    ->label('Prefixe')
                    ->copyable()
                    ->fontFamily('mono'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('last_used_at')
                    ->label('Derniere utilisation')
                    ->dateTime('d/m/Y H:i')
                    ->since()
                    ->placeholder('Jamais utilisee'),

                TextColumn::make('last_used_ip')
                    ->label('Derniere IP')
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Creee le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->trueLabel('Active')
                    ->falseLabel('Revoquee'),

                SelectFilter::make('merchant_profile_id')
                    ->label('Marchand')
                    ->relationship('merchantProfile', 'business_name')
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Revoquer')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (MerchantApiKey $record) => $record->is_active
                        && Auth::user()->can('revoke', $record))
                    ->requiresConfirmation()
                    ->modalDescription('Toute integration utilisant cette cle perdra immediatement l\'acces. Cette action est irreversible.')
                    ->action(function (MerchantApiKey $record) {
                        try {
                            app(RevokeMerchantApiKeyAction::class)->handle($record->merchantProfile, $record);

                            Notification::make()->title('Cle revoquee.')->success()->send();
                        } catch (TransactionRejectedException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->emptyStateHeading('Aucune cle API')
            ->emptyStateDescription('Les cles apparaissent ici des qu\'un marchand en genere depuis son tableau de bord.');
    }
}