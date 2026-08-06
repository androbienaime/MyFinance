<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Reviewed => 'Revu',
            self::Archived => 'Archive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Reviewed => 'success',
            self::Archived => 'gray',
        };
    }
}