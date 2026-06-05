<?php

namespace App\Filament\Resources\ObligationResource\Pages;

use App\Filament\Resources\ObligationResource;
use App\Models\ObligationHistory;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditObligation extends EditRecord
{
    protected static string $resource = ObligationResource::class;

    private string $oldStatus;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldStatus = $this->record->status;

        return $data;
    }

    protected function afterSave(): void
    {
        $notes = 'Obrigação editada.';
        if (isset($this->oldStatus) && $this->oldStatus !== $this->record->status) {
            $labels   = \App\Models\Obligation::statusOptions();
            $oldLabel = $labels[$this->oldStatus] ?? $this->oldStatus;
            $newLabel = $labels[$this->record->status] ?? $this->record->status;
            $notes    = "Status alterado de '$oldLabel' para '$newLabel'.";
        }

        ObligationHistory::create([
            'obligation_id' => $this->record->id,
            'action'        => $notes,
            'old_value'     => $this->oldStatus ?? null,
            'new_value'     => $this->record->status,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Ver'),
            Actions\DeleteAction::make()->label('Excluir'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
