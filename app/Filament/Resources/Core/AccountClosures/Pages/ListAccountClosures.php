<?php

namespace App\Filament\Resources\Core\AccountClosures\Pages;

use App\Enums\TransactionType;
use App\Filament\Resources\Core\AccountClosures\AccountClosureResource;
use App\Models\Core\Account;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListAccountClosures extends ListRecords
{
    protected static string $resource = AccountClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->action(function (array $data): void {
                    $account = Account::find($data['account_id']);

                    if (!$account) {
                        Notification::make()
                            ->title('Compte introuvable')
                            ->warning()
                            ->send();

                        return;
                    }

                    $employeeId = Auth::user()->employee?->id;

                    if (!$employeeId) {
                        Notification::make()
                            ->title('Aucun employé associé à cet utilisateur')
                            ->danger()
                            ->send();

                        return;
                    }

                    $closure = $account->closeAccount($data['reason'], $employeeId);

                    Notification::make()
                        ->title($closure ? 'Compte fermé avec succès.' : 'Échec de la fermeture du compte.')
                        ->color($closure ? 'success' : 'danger')
                        ->send();
                }),
        ];
    }
}
