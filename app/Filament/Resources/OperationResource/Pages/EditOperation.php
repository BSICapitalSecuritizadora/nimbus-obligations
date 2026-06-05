<?php

namespace App\Filament\Resources\OperationResource\Pages;

use App\Filament\Resources\OperationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOperation extends EditRecord
{
    protected static string $resource = OperationResource::class;

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
