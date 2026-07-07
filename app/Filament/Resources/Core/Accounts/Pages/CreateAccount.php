<?php

namespace App\Filament\Resources\Core\Accounts\Pages;

use App\Filament\Resources\Core\Accounts\AccountResource;
use App\Models\Core\Account;
use App\Models\Core\TypeOfAccount;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $typeOfAccount = TypeOfAccount::findOrFail($data['type_of_account_id']);

        $data['code'] = Account::generateUniqueCode($typeOfAccount);

        return $data;
    }
}
