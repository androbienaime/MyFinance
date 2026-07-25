<?php

namespace App\Filament\Resources\Core\P2pTransferRequests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class P2pTransferRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'id')
                    ->required(),
                Select::make('from_account_id')
                    ->relationship('fromAccount', 'id')
                    ->required(),
                Select::make('to_account_id')
                    ->relationship('toAccount', 'id')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('fee_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('otp_code_hash')
                    ->required(),
                TextInput::make('attempts')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('expires_at')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                DateTimePicker::make('confirmed_at'),
            ]);
    }
}
