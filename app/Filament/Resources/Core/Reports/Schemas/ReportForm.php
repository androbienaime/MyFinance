<?php

namespace App\Filament\Resources\Core\Reports\Schemas;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Core\CaisseSession;
use App\Models\Core\Transaction;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Auth;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make("infos")
                ->schema([
                    \Filament\Schemas\Components\Text::make(
                        fn ($record) => $record?->status === ReportStatus::Draft
                            ? ($record ? '📝 Ce rapport est en brouillon.' : null)
                            : '⚠️ Ce rapport a été soumis, il n\'est plus modifiable.'

                    )
                ])
                ->dehydrated(true)
                ->visible(fn ($record) => $record !== null)
                ->columnSpanFull(),
                Select::make('category')
                        ->label('Categorie')
                        ->options(ReportCategory::manualOptions())
                        ->required()
                        ->native(false),
                TextInput::make('title')
                    ->required(),
                RichEditor::make('content')
                        ->label('Contenu du rapport')
                        ->required()
                        ->minLength(10)
                        // ->rows(8)
                        ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        DatePicker::make('period_start')
                            ->label('Debut de periode')
                            ->native(false),
                        DatePicker::make('period_end')
                            ->label('Fin de periode')
                            ->native(false)
                            ->afterOrEqual('period_start'),
                    ]),

            // Section purement informative : rien ici n'est enregistre, ca
            // sert uniquement de reference chiffree pour aider l'employe a
            // equilibrer/verifier avant de rediger son rapport.
            Section::make('Contexte systeme')
                ->description("Chiffres calcules automatiquement a partir de vos transactions sur la periode choisie ci-dessus — a titre indicatif, pour vous aider a equilibrer.")
                ->icon('heroicon-o-calculator')
                ->collapsible()
                ->schema([
                    Grid::make(4)
                        ->schema([
                            Placeholder::make('context_deposits')
                                ->label('Total depots')
                                ->content(fn (Get $get) => number_format(
                                    self::employeeTransactions($get)->where('type', TransactionType::Deposit)->sum('amount'),
                                    2
                                )),
                            Placeholder::make('context_withdrawals')
                                ->label('Total retraits')
                                ->content(fn (Get $get) => number_format(
                                    self::employeeTransactions($get)->where('type', TransactionType::Withdrawal)->sum('amount'),
                                    2
                                )),
                            Placeholder::make('context_net')
                                ->label('Flux net')
                                ->content(function (Get $get) {
                                    $transactions = self::employeeTransactions($get);
                                    $net = $transactions->where('type', TransactionType::Deposit)->sum('amount')
                                        - $transactions->where('type', TransactionType::Withdrawal)->sum('amount');

                                    return number_format($net, 2);
                                }),
                            Placeholder::make('context_count')
                                ->label('Nombre de transactions')
                                ->content(fn (Get $get) => self::employeeTransactions($get)->count()),
                        ]),

                    Placeholder::make('context_caisse')
                        ->label('Caisse')
                        ->content(fn () => self::caisseSummary())
                        ->columnSpanFull(),
                ]),
            ]);
    }

     /**
     * Transactions completees de l'employe connecte, sur la periode
     * actuellement selectionnee dans le formulaire (par defaut aujourd'hui).
     */
    protected static function employeeTransactions(Get $get)
    {
        $employeeId = auth()->user()->employee?->id;

        $start = $get('period_start') ? SupportCarbon::parse($get('period_start'))->startOfDay() : now()->startOfDay();
        $end = $get('period_end') ? SupportCarbon::parse($get('period_end'))->endOfDay() : now()->endOfDay();

        return Transaction::query()
            ->where('employee_id', $employeeId)
            ->where('status', TransactionStatus::Completed->value)
            ->whereBetween('created_at', [$start, $end])
            ->get();
    }

    /**
     * Etat de la caisse du jour pour ce caissier : ouverte (avec solde
     * attendu courant) ou fermee (avec l'ecart constate, s'il y en a un).
     */
    protected static function caisseSummary(): string
    {
        $employeeId = auth()->user()->employee?->id;

        $session = CaisseSession::query()
            ->where('employee_id', $employeeId)
            ->forDate(now())
            ->first();

        if (! $session) {
            return 'Aucune caisse ouverte aujourd\'hui.';
        }

        if ($session->status->value === 'open') {
            return sprintf(
                'Caisse ouverte depuis %s — solde d\'ouverture declare : %s',
                $session->opened_at->format('H:i'),
                number_format((float) $session->opening_balance_declared, 2)
            );
        }

        $ecart = (float) $session->closing_discrepancy;

        return sprintf(
            'Caisse fermee a %s — solde declare : %s, ecart : %s%s',
            $session->closed_at->format('H:i'),
            number_format((float) $session->closing_balance_declared, 2),
            number_format($ecart, 2),
            $ecart !== 0.0 ? ' ⚠️' : ' ✓'
        );
    }
}
