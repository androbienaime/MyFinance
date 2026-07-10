<?php

namespace App\Filament\Resources\Core\AccountClosures\Schemas;

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
                    ->getOptionLabelFromRecordUsing(fn (Account $record) => trim("{$record?->customer?->person?->full_name}"))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function($state, callable $set){
                            $account = Account::where('id', $state)->first();
                            if(!$account){
                                return ;
                            }

                            $set("balance_at_closure", $account->balance);
                    })
                    ->required(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('reason')
                    ->default(null),
                TextInput::make('balance_at_closure')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->visible(false)
                    ->default(0.0),
                TextInput::make('closed_by')
                    ->numeric()
                    ->disabled()
                    ->default(auth()->user()->employee->id)
                    ->visible(false),
            ]);
    }
}
