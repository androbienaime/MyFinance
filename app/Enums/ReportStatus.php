<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case Archived = 'archived';
    case Draft = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Reviewed => 'Revu',
            self::Archived => 'Archive',
            self::Draft => "Brouillon",
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Reviewed => 'success',
            self::Archived => 'gray',
            self::Draft => "gray",
        };
    }
}