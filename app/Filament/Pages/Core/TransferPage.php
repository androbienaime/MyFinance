<?php

namespace App\Filament\Pages\Core;

use App\Actions\TransferAction;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Filament\Pages\Concerns\TransactionsTableTrait;
use App\Models\Core\Account;
use App\Models\Core\Currency;
use App\Models\Core\P2pTransferFeeTier;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TransferPage extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas;
    use InteractsWithTable;
    use TransactionsTableTrait {
        TransactionsTableTrait::table insteadof InteractsWithTable;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static string|UnitEnum|null $navigationGroup = 'Operations';
    protected static ?string $navigationLabel = 'Virements';
    protected static ?string $title = 'Virements';
    protected string $view = 'filament.pages.core.transfer-page';

    /** Cache local pour eviter de re-interroger la DB a chaque render Livewire. */
    private array $accountCache = [];

    public static function getNavigationLabel(): string
    {
        return __('myfinance.transfer');
    }

    public static function getNavigationGroup(): string
    {
        return __('myfinance.operations');
    }

    public static function canAccess(): bool
    {
        if (! setting('transactions.transfer_enabled')) {
            return false;
        }

        return auth()->user()?->can('transactions.transfer') ?? false;
    }

    protected function transactionsTableScope($query): void
    {
        // $query->where('type', TransactionType::Transfer);

        $query->whereIn('type', [
            TransactionType::Transfer,
        ]);
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    /**
     * Point d'entree unique de resolution de compte, avec cache memoire
     * pour la duree de vie de l'instance (evite les requetes dupliquees
     * entre hydrateAccountPreview, le calcul de frais et les prefixes).
     */
    private function resolveAccount(?string $code): ?Account
    {
        if (blank($code)) {
            return null;
        }

        return $this->accountCache[$code] ??= Account::where('code', $code)
            ->with(['customer.person', 'currency'])
            ->first();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->columnSpanFull()
                ->schema([
                    TextInput::make('from_account_code')
                        ->label('Compte source')
                        ->required()
                        ->live(debounce: 600)
                        ->afterStateUpdated(fn ($state, callable $set, Get $get) => $this->hydrateAccountPreview($state, $set, $get, 'from')),

                    TextInput::make('from_full_name')
                        ->label('Titulaire source')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (Get $get) => $get('from_full_name') ?: '—'),

                    TextInput::make('from_balance')
                        ->label('Solde disponible')
                        ->disabled()
                        ->dehydrated(false)
                        ->prefix(fn (Get $get) => $get('prefix_field_from') ?: '')
                        ->columnSpanFull()
                        ->formatStateUsing(fn (Get $get) => number_format((float) ($get('from_balance') ?? 0), 2)),

                    TextInput::make('to_account_code')
                        ->label('Compte destinataire')
                        ->required()
                        ->live(debounce: 600)
                        ->afterStateUpdated(fn ($state, callable $set, Get $get) => $this->hydrateAccountPreview($state, $set, $get, 'to')),

                    TextInput::make('to_full_name')
                        ->label('Titulaire destinataire')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (Get $get) => $get('to_full_name') ?: '—'),

                    TextInput::make('amount')
                        ->label('Montant')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->live(onBlur: 600)
                        ->afterStateUpdated(fn ($state, Set $set, Get $get) => $this->recomputeAmountDerivedFields((float) $state, $set, $get))
                        ->prefix(fn (Get $get) => $get('prefix_field_from') ?: '')
                        ->columnSpanFull(),

                    TextInput::make('converted_amount')
                        ->label('Montant recu par le destinataire')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (Get $get) => filled($get('converted_amount')))
                        ->prefix(fn (Get $get) => $get('prefix_field_to') ?: '')
                        ->columnSpanFull(),

                    TextInput::make('fee_amount')
                        ->label('Frais')
                        ->disabled()
                        ->dehydrated()
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->visible(setting('financial.fee_for_transfer_in_branch_enabled', default: false))
                        ->prefix(fn (Get $get) => $get('prefix_field_from') ?: '')
                        ->hint(fn (Get $get) => $get('fee_hint') ?: null)
                        ->columnSpanFull(),
                ]),
        ])->statePath('data');
    }

    private function hydrateAccountPreview(?string $code, callable $set, Get $get, string $prefix): void
    {
        $set("{$prefix}_full_name", '');
        $set("{$prefix}_balance", '');
        $set("prefix_field_{$prefix}", '');
        $set('fee_hint', ''); // ajoute ici, avant le early-return si compte introuvable

        $account = $this->resolveAccount($code);

        if (! $account) {
            if ($prefix === 'to') {
                $set('converted_amount', '');
            }
            return;
        }

        $set("{$prefix}_full_name", $account->customer?->person?->full_name ?? 'Client inconnu');
        $set("prefix_field_{$prefix}", $account->currency?->symbol ?? '');

        if ($prefix === 'from') {
            $set('from_balance', (float) $account->availableBalance());
        }

        // Un changement de compte (source ou destination) invalide
        // l'apercu de conversion precedent - on le recalcule si un
        // montant est deja saisi.
        $amount = (float) ($get('amount') ?? 0);
        if ($amount > 0) {
            $this->recomputeAmountDerivedFields($amount, $set, $get);
        }
    }

    private function recomputeAmountDerivedFields(float $amount, Set $set, Get $get): void
    {
        $from = $this->resolveAccount($get('from_account_code'));
        $to = $this->resolveAccount($get('to_account_code'));

        if (! $from || $amount <= 0) {
            $set('fee_amount', 0);
            $set('fee_hint', '');
            $set('converted_amount', '');
            return;
        }

        $sourceCurrency = $from->currency;
        $defaultCurrency = Currency::default();
        $sourceIsDefault = $sourceCurrency->is($defaultCurrency);

        $amountInDefaultCurrency = $sourceIsDefault
            ? $amount
            : $sourceCurrency->convertTo($amount, $defaultCurrency);

        $feeEnabled = setting('financial.fee_for_transfer_in_branch_enabled', default: false);

        if (! $feeEnabled) {
            $set('fee_amount', 0);
            $set('fee_hint', '');
        } else {
            $feeInDefaultCurrency = P2pTransferFeeTier::feeFor($amountInDefaultCurrency);

            $feeInSourceCurrency = $sourceIsDefault
                ? $feeInDefaultCurrency
                : $defaultCurrency->convertTo($feeInDefaultCurrency, $sourceCurrency);

            $set('fee_amount', $feeInSourceCurrency);

            // Hint uniquement si la devise source differe de la devise par
            // defaut - sinon l'info serait redondante avec le champ lui-meme.
            $set('fee_hint', $sourceIsDefault
                ? ''
                : sprintf(
                    '≈ %s (tarif calcule sur cette base, taux: 1 %s = %s %s)',
                    $defaultCurrency->format($feeInDefaultCurrency),
                    $sourceCurrency->code,
                    number_format($sourceCurrency->rateTo($defaultCurrency), 4),
                    $defaultCurrency->code,
                ));
        }

        if (! $to) {
            $set('converted_amount', '');
            return;
        }

        $destinationCurrency = $to->currency;

        $set('converted_amount', $sourceCurrency->is($destinationCurrency)
            ? ''
            : number_format($sourceCurrency->convertTo($amount, $destinationCurrency), $destinationCurrency->decimal_places));
    }
    public function submitTransaction(): void
    {
        $state = $this->form->getState();
        $employee = Auth::user()->employee;

        if (! $employee) {
            Notification::make()->title('Aucune fiche employe associee a votre compte.')->danger()->send();
            return;
        }

        if (! Auth::user()->can('transactions.transfer')) {
            Notification::make()->title('Vous n\'avez pas le droit d\'effectuer un virement.')->danger()->send();
            return;
        }

        try {
            app(TransferAction::class)->handle(
                $state['from_account_code'],
                $state['to_account_code'],
                (float) ($state['amount'] ?? 0),
                employee: $employee,
            );

            Notification::make()->title('Virement enregistre.')->success()->send();

            $this->accountCache = [];
            $this->form->fill();
            $this->resetTable();
        } catch (TransactionRejectedException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}