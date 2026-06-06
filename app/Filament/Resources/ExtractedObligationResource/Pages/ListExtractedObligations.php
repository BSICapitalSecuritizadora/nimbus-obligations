<?php

namespace App\Filament\Resources\ExtractedObligationResource\Pages;

use App\Filament\Resources\ExtractedObligationResource;
use Filament\Resources\Pages\ListRecords;

class ListExtractedObligations extends ListRecords
{
    protected static string $resource = ExtractedObligationResource::class;

    protected string|int|null $defaultTableRecordsPerPageSelectOption = 25;
}
