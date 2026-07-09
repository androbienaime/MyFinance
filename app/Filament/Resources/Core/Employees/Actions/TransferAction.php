<?php
// app/Filament/Resources/EmployeeResource/Actions/TransferAction.php

namespace App\Filament\Resources\EmployeeResource\Actions;

use App\Models\Core\Branch;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class TransferAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'transfer';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Transférer');
        $this->icon('heroicon-o-arrow-path');

        $this->form([
            Forms\Components\Select::make('branch_id')
                ->label('Nouvelle Branch')
                ->options(fn ($record) => Branch::where('id', '!=', $record->branch_id)
                    ->pluck('name', 'id'))
                ->required(),

            Forms\Components\DateTimePicker::make('effective_date')
                ->label("Date d'effet")
                ->default(now())
                ->required(),

            Forms\Components\TextInput::make('reason')
                ->label('Motif')
                ->default('transfert')
                ->required(),
        ]);

        $this->action(function (array $data, $record) {
            $record->transferToBranch(
                newBranch: Branch::findOrFail($data['branch_id']),
                reason: $data['reason'],
                performedBy: Auth::id(),
                effectiveDate: Carbon::parse($data['effective_date']),
            );

            Notification::make()
                ->success()
                ->title('Employé transféré avec succès')
                ->send();
        });
    }
}