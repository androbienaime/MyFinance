<?php

namespace App\Filament\Resources\Core\ApprovalThresholds\Pages;

use App\Filament\Resources\Core\ApprovalThresholds\ApprovalThresholdResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApprovalThresholds extends ListRecords
{
    protected static string $resource = ApprovalThresholdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
