<?php

namespace App\Enums;

enum ReportCategory: string
{
    // Categories automatiques (generees par le systeme)
    case DailyClosing = 'daily_closing';
    case MonthlySummary = 'monthly_summary';

    // Categories manuelles (redigees par un employe)
    case Incident = 'incident';
    case CashDiscrepancy = 'cash_discrepancy';
    case CustomerComplaint = 'customer_complaint';
    case Maintenance = 'maintenance';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::DailyClosing => 'Cloture journaliere',
            self::MonthlySummary => 'Resume mensuel',
            self::Incident => 'Incident',
            self::CashDiscrepancy => 'Ecart de caisse',
            self::CustomerComplaint => 'Plainte client',
            self::Maintenance => 'Maintenance',
            self::Custom => 'Autre',
        };
    }

    public static function manualOptions(): array
    {
        return [
            self::Incident->value => self::Incident->label(),
            self::CashDiscrepancy->value => self::CashDiscrepancy->label(),
            self::CustomerComplaint->value => self::CustomerComplaint->label(),
            self::Maintenance->value => self::Maintenance->label(),
            self::Custom->value => self::Custom->label(),
        ];
    }
}