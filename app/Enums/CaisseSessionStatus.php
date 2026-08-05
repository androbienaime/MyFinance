<?php

namespace App\Enums;

enum CaisseSessionStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::Closed => 'Fermee',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::Closed => 'success',
        };
    }
}