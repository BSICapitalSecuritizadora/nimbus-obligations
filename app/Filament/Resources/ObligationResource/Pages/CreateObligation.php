<?php

namespace App\Filament\Resources\ObligationResource\Pages;

use App\Filament\Resources\ObligationResource;
use App\Models\ObligationHistory;
use Filament\Resources\Pages\CreateRecord;

class CreateObligation extends CreateRecord
{
    protected static string $resource = ObligationResource::class;

    protected function afterCreate(): void
    {
        ObligationHistory::create([
            'obligation_id' => $this->record->id,
            'action'        => 'Obrigação criada manualmente.',
            'new_value'     => $this->record->status,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
