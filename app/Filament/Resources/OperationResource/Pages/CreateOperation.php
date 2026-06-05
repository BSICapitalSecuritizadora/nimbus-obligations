<?php

namespace App\Filament\Resources\OperationResource\Pages;

use App\Filament\Resources\OperationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOperation extends CreateRecord
{
    protected static string $resource = OperationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
