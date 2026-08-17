<?php

namespace App\Filament\Resources\Core\Reports\Pages;

use App\Enums\ReportStatus;
use App\Filament\Resources\Core\Reports\ReportResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditReport extends EditRecord
{
    protected static string $resource = ReportResource::class;

    protected ?ReportStatus $targetStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
            
        ];
    }

     protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->targetStatus) {
            $data['status'] = $this->targetStatus->value;
        }

        return $data;
    }

    protected function getFormActions(): array
    {
        $isDraft = $this->record->status === ReportStatus::Draft;

        return [
            Action::make('saveDraft')
                ->label('Enregistrer le brouillon')
                ->color(ReportStatus::Draft->color())
                ->visible($isDraft)
                ->action(function () {
                    $this->targetStatus = ReportStatus::Draft;
                    $this->save();
                }),

            Action::make('submit')
                ->label('Soumettre')
                ->color('primary')
                ->visible($isDraft)
                ->action(function () {
                    $this->targetStatus = ReportStatus::Pending;
                    $this->save();
                }),

            $this->getCancelFormAction(),
        ];
    }
}
