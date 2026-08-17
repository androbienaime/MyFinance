<?php

namespace App\Filament\Resources\Core\Reports\Pages;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Filament\Resources\Core\Reports\ReportResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;

    protected ReportStatus $targetStatus = ReportStatus::Pending;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = ReportType::Manual->value;
        $data['status'] = $this->targetStatus->value;
        $data['employee_id'] = auth()->user()->employee?->id;
        $data['branch_id'] = auth()->user()->currentBranchId();
 
        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('saveDraft')
                ->label('Enregistrer comme brouillon')
                ->color(ReportStatus::Draft->color())
                ->action(function () {
                    $this->targetStatus = ReportStatus::Draft;
                    $this->create();
                }),

            Action::make('submit')
                ->label('Soumettre')
                ->color('primary')
                ->action(function () {
                    $this->targetStatus = ReportStatus::Pending;
                    $this->create();
                }),

            $this->getCancelFormAction(),
        ];
    }
}
