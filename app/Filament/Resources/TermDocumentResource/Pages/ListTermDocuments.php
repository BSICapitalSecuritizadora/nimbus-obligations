<?php

namespace App\Filament\Resources\TermDocumentResource\Pages;

use App\Filament\Resources\TermDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTermDocuments extends ListRecords
{
    protected static string $resource = TermDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Fazer Upload de Termo'),
        ];
    }
}
