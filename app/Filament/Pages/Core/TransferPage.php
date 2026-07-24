<?php

namespace App\Filament\Pages\Core;

use App\Actions\TransferAction;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Filament\Pages\Concerns\TransactionsTableTrait;
use App\Models\Core\Account;
use App\Models\Core\Transaction;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
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
        $query->where('type', TransactionType::Transfer);
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
                    TextInput::make('from_account_code')
                        ->label('Compte source')
                        ->required()
                        ->live(debounce: 600)
                        ->afterStateUpdated(fn ($state, callable $set) => $this->hydrateAccountPreview($state, $set, 'from')),

                    TextInput::make('from_full_name')
                        ->label('Titulaire source')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (Get $get) => $get('from_full_name') ?: '—'),

                    TextInput::make('from_balance')
                        ->label('Solde disponible')
                        ->disabled()
                        ->dehydrated(false)
                        ->prefix('HTG')
                        ->columnSpanFull()
                        ->formatStateUsing(fn (Get $get) => number_format((float) ($get('from_balance') ?? 0), 2)),

                    TextInput::make('to_account_code')
                        ->label('Compte destinataire')
                        ->required()
                        ->live(debounce: 600)
                        ->afterStateUpdated(fn ($state, callable $set) => $this->hydrateAccountPreview($state, $set, 'to')),

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
                        ->prefix('HTG')
                        ->columnSpanFull(),
                ]),
        ])->statePath('data');
    }

    private function hydrateAccountPreview($code, callable $set, string $prefix): void
    {
        $set("{$prefix}_full_name", '');
        $set("{$prefix}_balance", '');

        if (blank($code)) {
            return;
        }

        $account = Account::where('code', $code)->with('customer.person')->first();

        if (! $account) {
            return;
        }

        $set("{$prefix}_full_name", $account->customer?->person?->full_name ?? 'Client inconnu');

        if ($prefix === 'from') {
            $set('from_balance', (float) $account->availableBalance());
        }
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

            $this->form->fill();
            $this->resetTable();
        } catch (TransactionRejectedException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}