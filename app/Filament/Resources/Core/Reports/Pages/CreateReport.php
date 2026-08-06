<?php

namespace App\Filament\Resources\Core\Reports\Pages;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Filament\Resources\Core\Reports\ReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = ReportType::Manual->value;
        $data['status'] = ReportStatus::Pending->value;
        $data['employee_id'] = auth()->user()->employee?->id;
        $data['branch_id'] = auth()->user()->currentBranchId();
 
        return $data;
    }
}
