<?php

namespace App\Filament\Resources\Core\MerchantApiKeys\Schemas;

use Filament\Schemas\Schema;

class MerchantApiKeyForm
{
    public function canAccess(): bool
    {
        return false; // uniquement genere par le marchand lui-meme via l'API
    }
    
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
