<?php

namespace App\Filament\Resources\ObligationResource\Pages;

use App\Filament\Resources\ObligationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListObligations extends ListRecords
{
    protected static string $resource = ObligationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nova Obrigação'),
        ];
    }
}
