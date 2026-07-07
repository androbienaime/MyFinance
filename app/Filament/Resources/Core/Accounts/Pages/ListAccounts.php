<?php

namespace App\Filament\Resources\Core\Accounts\Pages;

use App\Filament\Resources\Core\Accounts\AccountResource;
use App\Models\Core\Account;
use App\Models\Core\TypeOfAccount;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    public ?string $createdAccountCode = null;

   protected function getHeaderActions(): array
{
    return [
        CreateAction::make()
            ->mutateFormDataUsing(function (array $data): array {
                $typeOfAccount = TypeOfAccount::findOrFail($data['type_of_account_id']);
                $data['code'] = Account::generateUniqueCode($typeOfAccount);

                return $data;
            })
            ->registerModalActions([
                Action::make('accountCreatedModal')
                    ->label('')
                    ->modalHeading('Compte créé avec succès')
                    ->modalDescription(fn (array $arguments) => "Numéro de compte : {$arguments['code']}")
                    ->modalIcon('heroicon-o-check-circle')
                    ->modalIconColor('success')
                    ->modalSubmitActionLabel('Fermer')
                    ->modalCancelAction(false) // on retire le bouton "Annuler", il n'y a plus qu'un bouton
                    ->action(fn () => null)    // no-op : on ne fait rien, on veut juste fermer proprement
                    ->cancelParentActions(),
            ])
            ->after(function (Action $action, Account $record) {
                $this->mountAction('accountCreatedModal', arguments: [
                    'code' => $record->code,
                ]);
            }),
    ];
}
}
