<?php

namespace App\Filament\Resources\Core\ApprovalThresholds\Pages;

use App\Filament\Resources\Core\ApprovalThresholds\ApprovalThresholdResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApprovalThreshold extends EditRecord
{
    protected static string $resource = ApprovalThresholdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
