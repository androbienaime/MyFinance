<?php

namespace App\Filament\Pages\Core;

use App\Actions\WithdrawAction;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Filament\Pages\Concerns\TransactionsTableTrait;
use App\Models\Core\Account;
use App\Models\Core\Transaction;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class WithdrawPage extends Page implements HasSchemas, HasTable
{
       use InteractsWithSchemas;
        use InteractsWithTable;
        use TransactionsTableTrait {
            TransactionsTableTrait::table insteadof InteractsWithTable;
        }
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Retraits';

    protected static ?string $title = 'Retraits';

    protected string $view = 'filament.pages.withdraw-page';

     public static function getNavigationLabel(): string
    {
        return __('myfinance.withdrawal');
    }

    public static function getNavigationGroup(): string
    {
        return __('myfinance.operations');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('transactions.withdraw') ?? false;
    }

    protected function showTransferColumns(): bool
    {
        return false;
    }

    protected function transactionsTableScope($query): void
    {
        $query->whereIn('type', [
            TransactionType::Deposit,
            TransactionType::Withdrawal,
            TransactionType::AccountSettlement,
        ]);
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

 public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->columnSpanFull()
                ->schema([
                    TextInput::make('account_code')
                        ->label(__('myfinance.account_code'))
                        ->required()
                        ->live(debounce: 600)
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('active_case_payments', false);
                            $set('account_active', null);
                            $set('full_name', '');
                            $set('balance', '');
                            $set('references_people', '');
                            $set('total_amount_hint', '');
                            $set('prefix_field', setting("financial.default_currency", default:'HTG'));

                            // Toujours reset l'erreur avant toute nouvelle recherche
                            $this->resetErrorBag('data.full_name');

                            if (blank($state)) {
                                return;
                            }

                            $account = Account::where('code', $state)
                                ->with('typeOfAccount', 'tagsPayments', 'customer.person')
                                ->first();

                            if (! $account) {
                                if (strlen($state) > 4) {
                                    Notification::make()
                                        ->title('Aucun compte ne correspond a ce code.')
                                        ->warning()
                                        ->send();
                                    
                                    $this->addError('data.full_name', 'Compte Introuvable.');

                                }
                                return;
                            }

                            $set('account_active', (bool) $account->is_active);
                            $set('full_name', $account->customer?->person?->full_name ?? 'Client inconnu');
                            $set('balance', (float) $account->balance);
                            $set('prefix_field', $account?->currency?->symbol);

                            $hasOperationToAccount = (bool) ($account->typeOfAccount->active_case_payments ?? false);
                            $set('has_operation_to_account', $hasOperationToAccount);

                            $set('references_people', $account->getAccountInfos());

                            if (! $account->is_active) {
                                Notification::make()
                                    ->title('Ce compte est desactive.')
                                    ->body('Aucun depot ne peut etre enregistre tant que le compte n\'est pas reactive.')
                                    ->danger()
                                    ->send();

                                $this->addError('data.full_name', 'Compte desactive.');
                                return;
                            }

                            if ($hasOperationToAccount) {
                                $this->addError('data.full_name', 'Vous ne pouvez faire de retrait sur ce type de compte.');
                            }
                        }),

                    TextInput::make('full_name')
                        ->label(__('myfinance.account_holder'))
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (Get $get) => $get('full_name') ?: '—')
                        ->hint(fn (Get $get) => $get('account_active') === false ? 'Inactif' : null)
                        ->hintColor('danger')
                        ->hintIcon(fn (Get $get) => $get('account_active') === false ? Heroicon::ExclamationTriangle : null),
 
                    TextInput::make('balance')
                        ->label(__('myfinance.current_balance'))
                        ->disabled()
                        ->dehydrated(false)
                        // prefix_field est deja rempli (et reset) dans
                        // afterStateUpdated de account_code - plus besoin de
                        // fallback qui refaisait une requete Account a chaque
                        // repaint reactif du champ.
                        ->prefix(fn (Get $get) => $get('prefix_field'))
                        ->formatStateUsing(fn (Get $get) => number_format((float) ($get('balance') ?? 0), 2))
                        ->hint(fn (Get $get) => $get('account_active') === false ? 'Inactif' : null)
                        ->hintColor('danger')
                        ->columnSpanFull(),
 
                    TextInput::make('amount')
                        ->label(__('myfinance.amount'))
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->live(onBlur: true)
                        // Idem : plus de requete Account de secours, prefix_field
                        // est deja disponible en state.
                        ->prefix(fn (Get $get) => $get('prefix_field'))
                        ->hint(fn (Get $get) => $get('account_active') === false
                            ? 'Inactif'
                            : ($get('total_amount_hint') ?: null))
                        ->columnSpanFull()
                    // Impossible de saisir un montant sur un compte
                    // desactive. Pour un compte a cases, le champ reste
                    // actif : il sert (1) d'affichage en lecture reactive
                    // du total des cases cochees (mis a jour par Alpine
                    // via l'evenement navigateur "case-total-updated",
                    // sans jamais notifier $wire pour ne pas bloquer la
                    // saisie manuelle), et (2) de champ de saisie du
                    // montant cible pour le bouton "Generer" ci-dessous.
                    // Le montant final n'est de toute facon JAMAIS pris
                    // depuis ce champ pour un compte a cases : voir
                    // submitTransaction().
                    ->disabled(fn (Get $get) => $get('account_active') === false)
                    // Recalcule le solde futur apres retrait a partir du
                    // balance deja present en state - aucune requete DB.
                    ->afterStateUpdated(function ($state, callable $set, Get $get) {
                        $balance = (float) ($get('balance') ?? 0);
                        $amount = (float) ($state ?? 0);
                        $set('total_amount_hint', $this->computeTotalAmountHint($balance, $amount));
                    }),
                    Textarea::make('references_people')
                    ->label(__("myfinance.people_associated"))
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn (Get $get) => $get('full_name') ?: '—')
                    ->hint(fn (Get $get) => $get('account_active') === false ? 'Inactif' : null)
                    ->hintColor('danger')
                    ->hintIcon(fn (Get $get) => $get('account_active') === false ? Heroicon::ExclamationTriangle : null)
                    ->columnSpanFull(),
                ])
        ])->statePath('data');
    }

    public function submitTransaction(): void
    {
        $state = $this->form->getState();
        $employee = Auth::user()->employee;

        if (! $employee) {
            Notification::make()->title('Aucune fiche employe associee a votre compte.')->danger()->send();
            return;
        }

        if (! Auth::user()->can('createWithdrawal', Transaction::class)) {
            Notification::make()->title('Vous n\'avez pas le droit d\'effectuer un retrait.')->danger()->send();
            return;
        }

        try {
            $transaction = app(WithdrawAction::class)->handle($state['account_code'], (float) ($state['amount'] ?? 0), $employee);

            Notification::make()->title("Retrait {$transaction->code} enregistre.")->success()->send();

            $this->form->fill();
            $this->resetTable();
        } catch (TransactionRejectedException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    // Calcule le solde futur affiche en hint sous le champ Montant (apres
    // retrait), a partir du balance deja charge en state - aucune requete
    // DB, uniquement du calcul en memoire.
    private function computeTotalAmountHint(float $balance, float $amount): string
    {
        return sprintf(
            'Future Balance : %s',
            number_format($balance - $amount, 2)
        );
    }
}