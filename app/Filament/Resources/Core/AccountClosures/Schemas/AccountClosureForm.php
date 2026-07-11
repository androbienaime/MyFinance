<?php

namespace App\Filament\Resources\Core\AccountClosures\Schemas;

use App\Enums\TransactionType;
use App\Models\Core\Account;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class AccountClosureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('account_id')
                    ->relationship('account', 'id')
                    ->getOptionLabelFromRecordUsing(fn (Account $record) => trim("{$record?->customer?->person?->full_name} | {$record->code}"))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function($state, callable $set){
                        $account = Account::where("id", $state)->first();
                        $set("balance_at_closure", $account->balance);
                    })
                    ->required(),
                TextInput::make('reason')
                    ->default(null),
                TextInput::make('balance_at_closure')
                    ->required()
                    ->numeric()
                    ->prefix("HTG")
                    ->disabled()
                    ->visible(true)
                    ->default(0.0),
            ]);
    }
}
