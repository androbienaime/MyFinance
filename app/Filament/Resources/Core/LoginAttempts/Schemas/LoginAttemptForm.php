<?php

namespace App\Filament\Resources\Core\LoginAttempts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class LoginAttemptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('branch_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('ip_address')
                    ->required(),
                Textarea::make('user_agent')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
            'success' => 'Success',
            'failed_password' => 'Failed password',
            'failed_2fa' => 'Failed 2fa',
            'locked' => 'Locked',
            'blocked' => 'Blocked',
        ])
                    ->required(),
                TextInput::make('failure_reason')
                    ->default(null),
                DateTimePicker::make('attempted_at')
                    ->required(),
            ]);
    }
}
