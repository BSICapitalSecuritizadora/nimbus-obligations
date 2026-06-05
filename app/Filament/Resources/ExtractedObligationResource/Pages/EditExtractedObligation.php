<?php

namespace App\Filament\Resources\ExtractedObligationResource\Pages;

use App\Filament\Resources\ExtractedObligationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExtractedObligation extends EditRecord
{
    protected static string $resource = ExtractedObligationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Ver'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
