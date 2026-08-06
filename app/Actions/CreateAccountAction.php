<?php

// app/Actions/CreateAccountAction.php
namespace App\Actions;

use App\Enums\AccountHolderType;
use App\Enums\MerchantStatus;
use App\Enums\TransactionDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Account;
use App\Models\Core\AccountPerson;
use App\Models\Core\Currency;
use App\Models\Core\Customer;
use App\Models\Core\Employee;
use App\Models\Core\MerchantProfile;
use App\Models\Core\Person;
use App\Models\Core\Transaction;
use App\Models\Core\TypeOfAccount;
use Illuminate\Support\Facades\DB;

class CreateAccountAction
{
    /**
     * Point d'entree UNIQUE pour la creation d'un compte, peu importe le
     * contexte (nouveau client, client existant, futur import, API...).
     * Gere : le compte lui-meme, le titulaire principal (owner), les
     * personnes additionnelles (co-titulaires/mandataires), et les frais
     * de creation eventuels (montant configurable via setting()).
     *
     * @param array<int, array{first_name:string,last_name:string,gender?:string,role:string,share_percentage?:float}> $additionalPeople
     */
    public function handle(
        Customer $customer,
        TypeOfAccount $typeOfAccount,
        Currency $currency,
        Employee $employee,
        array $additionalPeople = [],
        ?float $creationFeeOverride = null,
        AccountHolderType $holderType = AccountHolderType::Personal,
        ?array $merchantData = null, // ['business_name' => ..., 'category' => ..., ...]
    ): Account {
        return DB::transaction(function () use (
            $customer, $typeOfAccount, $currency, $employee,
            $additionalPeople, $creationFeeOverride, $holderType, $merchantData
        ) {
            if ($holderType === AccountHolderType::Merchant && empty($merchantData['business_name'])) {
                throw new TransactionRejectedException('Le nom commercial est requis pour un compte marchand.');
            }

            $account = Account::create([
                'code' => Account::generateUniqueCode($typeOfAccount),
                'type_of_account_id' => $typeOfAccount->id,
                'holder_type' => $holderType,
                'customer_id' => $customer->id,
                'currency_id' => $currency->id,
                'balance' => 0,
                'is_active' => true,
                'employee_id' => $employee->id,
            ]);

            AccountPerson::create([
                'account_id' => $account->id,
                'person_id' => $customer->person_id,
                'role' => 'owner',
                'permissions' => ['view', 'withdraw', 'deposit'],
                'is_active' => true,
            ]);

            foreach ($additionalPeople as $item) {
                $person = Person::create([
                    'first_name' => $item['first_name'],
                    'last_name' => $item['last_name'],
                    'gender' => $item['gender'] ?? null,
                    'employee_id' => $employee->id,
                ]);

                AccountPerson::create([
                    'account_id' => $account->id,
                    'person_id' => $person->id,
                    'role' => $item['role'],
                    'share_percentage' => $item['share_percentage'] ?? null,
                    'permissions' => match ($item['role']) {
                        'co_owner' => ['view', 'withdraw', 'deposit'],
                        'attorney' => ['view', 'withdraw'],
                        default => ['view'],
                    },
                    'is_active' => true,
                ]);
            }

            if ($holderType === AccountHolderType::Merchant) {
                MerchantProfile::create([
                    'account_id' => $account->id,
                    'business_name' => $merchantData['business_name'],
                    'category' => $merchantData['category'] ?? null,
                    'business_registration_number' => $merchantData['business_registration_number'] ?? null,
                    'address' => $merchantData['address'] ?? null,
                    'status' => MerchantStatus::Pending, // validation manuelle requise avant activation
                ]);
            }

            $this->chargeCreationFeeIfAny($account, $employee, $creationFeeOverride);

            return $account;
        });
    }
    private function chargeCreationFeeIfAny(Account $account, Employee $employee, ?float $override): void
    {
        $feeAmount = $override ?? (float) setting('accounts.creation_fee_amount', 0);

        if ($feeAmount <= 0) {
            return;
        }

        $feesAccountCode = setting('accounts.creation_fee_account_code')
            ?: config('myfinance.fees_account_code');

        $feesAccount = Account::where('code', $feesAccountCode)->lockForUpdate()->first();

        if (! $feesAccount) {
            throw new TransactionRejectedException('Compte de frais de creation introuvable - contactez un administrateur.');
        }

        // Frais percu en especes au comptoir au moment de l'ouverture -
        // aucun debit sur le nouveau compte (son solde reste a 0), on
        // ne fait que crediter le compte de frais, en le liant au
        // nouveau compte via counterparty_account_id pour la tracabilite.
        Transaction::create([
            'account_id' => $feesAccount->id,
            'counterparty_account_id' => $account->id,
            'direction' => TransactionDirection::Credit,
            'code' => Transaction::generateUniqueCode(),
            'amount' => $feeAmount,
            'employee_id' => $employee->id,
            'type' => TransactionType::AccountCreationFee,
            'status' => TransactionStatus::Completed,
        ]);

        $feesAccount->increment('balance', $feeAmount);
    }
}