<?php

namespace App\Filament\Resources\Core\Customers\Pages;

use App\Actions\CreateAccountAction;
use App\Filament\Resources\Core\Customers\CustomerResource;
use App\Models\Core\Account;
use App\Models\Core\AccountPerson;
use App\Models\Core\Currency;
use App\Models\Core\Customer;
use App\Models\Core\Person;
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

    public ?string $createdAccountCode = null;

    // Stocke les champs "hors relation Person" retires du formulaire,
    // pour les reutiliser dans afterCreate() une fois le Customer
    // (et son Person/addresses) deja sauvegardes par Filament.
    protected ?int $pendingTypeOfAccountId = null;
    protected ?int $pendingCurrencyId = null;
    protected array $pendingAdditionalPeople = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingTypeOfAccountId = $data['type_of_account_id'] ?? null;
        $this->pendingCurrencyId = $data['currency_id'] ?? null;
        $this->pendingAdditionalPeople = $data['additional_account_people'] ?? [];
        // On retire ces cles : elles n'appartiennent ni a Customer ni a
        // Person, Filament ne doit pas essayer de les sauvegarder lui-meme.
        unset($data['type_of_account_id'], $data['additional_account_people'], $data['currency_id']);

        $data['code'] = 'CL-'.strtoupper(uniqid());
        $data['employee_id'] = Auth::user()->employee?->id;

        if(!empty($data["phone_number"])){
            $data["phone_number"] = $data["phonecode"].$data["phone_number"]; 
        }

        return $data;
    }

    /**
     * A ce stade, Filament a deja cree Person (+ addresses) ET Customer
     * (avec person_id correctement rempli) automatiquement, grace a
     * ->relationship('person') dans CustomerForm. Il ne reste plus qu'a
     * gerer ce qui n'est pas une relation Eloquent standard : le compte
     * et les personnes additionnelles.
     */
   protected function afterCreate(): void
    {
        if (! Auth::user()->can('create', Account::class)) {
            throw new AuthorizationException('Vous n\'avez pas le droit de creer un compte.');
        }

        try {
            $customer = $this->record;
            $employee = Auth::user()->employee;
            $typeOfAccount = TypeOfAccount::findOrFail($this->pendingTypeOfAccountId);
            $currency = Currency::find($this->pendingCurrencyId);

            if (! $currency) {
                throw new \RuntimeException('La devise selectionnee est introuvable.');
            }

            $account = app(CreateAccountAction::class)->handle(
                customer: $customer,
                typeOfAccount: $typeOfAccount,
                currency: $currency,
                employee: $employee,
                additionalPeople: $this->pendingAdditionalPeople,
            );

            $this->createdAccountCode = $account->code;
        } catch (\Throwable $e) {
            // Le Customer (et son Person associe) ont ete crees par Filament
            // AVANT afterCreate - si la creation du compte echoue, on les
            // supprime pour ne pas laisser d'enregistrements orphelins.
            $person = $this->record->person;

            $this->record->forceDelete();

            if ($person && $person->canBeDeleted()) {
                $person->delete();
            }

            throw $e;
        }

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
