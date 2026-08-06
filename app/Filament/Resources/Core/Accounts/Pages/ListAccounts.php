<?php
// app/Filament/Resources/Core/Accounts/Pages/ListAccounts.php
namespace App\Filament\Resources\Core\Accounts\Pages;

use App\Actions\CreateAccountAction;
use App\Enums\AccountHolderType;
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
                    $pendingHolderType = AccountHolderType::from($data['holder_type'] ?? 'personal');
                    $pendingMerchantData = $data['holder_type'] === 'merchant' ? [
                        'business_name' => $data['merchant_business_name'] ?? null,
                        'category' => $data['merchant_category'] ?? null,
                        'business_registration_number' => $data['merchant_business_registration_number'] ?? null,
                        'address' => $data['merchant_address'] ?? null,
                    ] : null;

                    unset(
                        $data['holder_type'],
                        $data['merchant_business_name'],
                        $data['merchant_category'],
                        $data['merchant_business_registration_number'],
                        $data['merchant_address'],
                    );

                    return app(CreateAccountAction::class)->handle(
                        customer: $customer,
                        typeOfAccount: $typeOfAccount,
                        currency: $currency,
                        employee: $employee,
                        holderType: $pendingHolderType,
                        merchantData: $pendingMerchantData,
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