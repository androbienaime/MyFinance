<?php

namespace App\Filament\Pages\Core;

use App\Actions\WithdrawAction;
use App\Exceptions\TransactionRejectedException;
use App\Filament\Pages\Concerns\TransactionsTableTrait;
use App\Models\Core\Transaction;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
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

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('account_code')->label('Code du compte')->required(),
                TextInput::make('amount')->label('Montant')->numeric()->minValue(0.01)->required(),
            ]),
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
            $transaction = app(WithdrawAction::class)->handle($state['account_code'], (float) $state['amount'], $employee);

            Notification::make()->title("Retrait {$transaction->code} enregistre.")->success()->send();

            $this->form->fill();
            $this->resetTable();
        } catch (TransactionRejectedException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}