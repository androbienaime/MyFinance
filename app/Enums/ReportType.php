<?php

namespace App\Enums;

enum ReportType: string
{
    case Automatic = 'automatic';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'Automatique',
            self::Manual => 'Manuel',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Automatic => 'info',
            self::Manual => 'gray',
        };
    }
}