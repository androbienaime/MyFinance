<?php
// app/Filament/Resources/Core/Accounts/Pages/ListAccounts.php
namespace App\Filament\Resources\Core\Accounts\Pages;

use App\Actions\CreateAccountAction;
use App\Filament\Resources\Core\Accounts\AccountResource;
use App\Models\Core\Account;
use App\Models\Core\Currency;
use App\Models\Core\Customer;
use App\Models\Core\TypeOfAccount;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    public ?string $createdAccountCode = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): Account {
                    $customer = Customer::findOrFail($data['customer_id']);
                    $typeOfAccount = TypeOfAccount::findOrFail($data['type_of_account_id']);
                    $currency = Currency::findOrFail($data['currency_id']);
                    $employee = Auth::user()->employee;

                    return app(CreateAccountAction::class)->handle(
                        customer: $customer,
                        typeOfAccount: $typeOfAccount,
                        currency: $currency,
                        employee: $employee,
                    );
                })
                ->registerModalActions([
                    Action::make('accountCreatedModal')
                        ->label('')
                        ->modalHeading('Compte créé avec succès')
                        ->modalDescription(fn (array $arguments) => "Numéro de compte : {$arguments['code']}")
                        ->modalIcon('heroicon-o-check-circle')
                        ->modalIconColor('success')
                        ->modalSubmitActionLabel('Fermer')
                        ->modalCancelAction(false)
                        ->action(fn () => null)
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