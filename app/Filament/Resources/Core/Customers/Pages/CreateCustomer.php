<?php

namespace App\Filament\Resources\Core\Customers\Pages;

use App\Filament\Resources\Core\Customers\CustomerResource;
use App\Models\Core\Account;
use App\Models\Core\AccountPerson;
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
    protected array $pendingAdditionalPeople = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingTypeOfAccountId = $data['type_of_account_id'] ?? null;
        $this->pendingAdditionalPeople = $data['additional_account_people'] ?? [];

        // On retire ces cles : elles n'appartiennent ni a Customer ni a
        // Person, Filament ne doit pas essayer de les sauvegarder lui-meme.
        unset($data['type_of_account_id'], $data['additional_account_people']);

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

        DB::transaction(function () {
            $customer = $this->record;
            $employeeId = Auth::user()->employee?->id;
            $typeOfAccount = TypeOfAccount::findOrFail($this->pendingTypeOfAccountId);

            $account = Account::create([
                'code' => Account::generateUniqueCode($typeOfAccount),
                'type_of_account_id' => $typeOfAccount->id,
                'customer_id' => $customer->id,
                'balance' => 0,
                'is_active' => true,
                'employee_id' => $employeeId,
            ]);

            // Le titulaire principal (deja cree par Filament via la
            // relation 'person') devient automatiquement "owner".
            AccountPerson::create([
                'account_id' => $account->id,
                'person_id' => $customer->person_id,
                'role' => 'owner',
                'permissions' => ['view', 'withdraw', 'deposit'],
                'is_active' => true,
            ]);

            foreach ($this->pendingAdditionalPeople as $item) {
                $person = Person::create([
                    'first_name' => $item['first_name'],
                    'last_name' => $item['last_name'],
                    'gender' => $item['gender'] ?? null,
                    'employee_id' => $employeeId,
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

            $this->createdAccountCode = $account->code;
        });

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
