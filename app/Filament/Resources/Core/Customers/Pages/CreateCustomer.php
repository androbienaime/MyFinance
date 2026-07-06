<?php

namespace App\Filament\Resources\Core\Customers\Pages;

use App\Filament\Resources\Core\Customers\CustomerResource;
use App\Models\Core\Account;
use App\Models\Core\TypeOfAccount;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Actions\StaticAction;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;


    /**
     * Stocke le code du compte cree pour l'afficher dans le modal
     * juste apres la sauvegarde (voir afterCreate() plus bas).
     */
    public ?string $createdAccountCode = null;

    protected function handleRecordCreation(array $data): Model
    {
        if (! Auth::user()->can('create', Account::class)) {
            throw new AuthorizationException('Vous n\'avez pas le droit de creer un compte.');
        }

        return DB::transaction(function () use ($data) {
            $typeOfAccount = TypeOfAccount::findOrFail($data['type_of_account_id']);
            // $initialBalance = $data['initial_balance'];

            $data['employee_id'] = Auth::user()->employee?->id;

            // Retire les champs "compte" avant de creer le Customer -
            // ils n'appartiennent pas a cette table.
            unset($data['type_of_account_id']);
            $data['code'] = 'CL-'.strtoupper(uniqid());

            $customer = static::getModel()::create($data);

            $account = Account::create([
                'code' => Account::generateUniqueCode($typeOfAccount),
                'type_of_account_id' => $typeOfAccount->id,
                'customer_id' => $customer->id,
                'balance' => 0,
                'is_active' => true,
                'employee_id' => Auth::user()->employee?->id,
            ]);

            $this->createdAccountCode = $account->code;

            return $customer;
        });
    }

    /**
     * Declenche le modal juste apres la sauvegarde reussie, avant la
     * redirection habituelle vers la liste.
     */
    protected function afterCreate(): void
    {
        $this->mountAction('accountCreatedModal');
    }

    /**
     * Empeche la redirection automatique de Filament vers la liste
     * juste apres afterCreate() - sans ça, la navigation coupe le
     * modal avant meme qu'il ait le temps de s'afficher a l'ecran.
     * La vraie redirection se fait manuellement, uniquement quand
     * l'utilisateur ferme le modal (voir l'action ci-dessous).
     */
    public function getRedirectUrl(): string
    {
        // Return empty string to prevent Filament's automatic redirect
        return '';
    }


    public function accountCreatedModalAction(): Action
    {
        return Action::make('accountCreatedModal')
            ->label('')
            ->modalHeading('Compte créé avec succès')
            ->modalDescription(fn () => "Numéro de compte : {$this->createdAccountCode}")
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('success')
            ->action(function () {
                // C'est ICI que la redirection doit être définie
                $this->redirect(CustomerResource::getUrl('index'));
            })
            ->modalSubmitAction(fn ($action) => $action->label('Fermer'))
            ->modalCancelAction(false);
    }
}
