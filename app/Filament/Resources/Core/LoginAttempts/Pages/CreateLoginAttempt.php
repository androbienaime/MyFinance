<?php

namespace App\Filament\Resources\Core\LoginAttempts\Pages;

use App\Filament\Resources\Core\LoginAttempts\LoginAttemptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLoginAttempt extends CreateRecord
{
    protected static string $resource = LoginAttemptResource::class;
}
