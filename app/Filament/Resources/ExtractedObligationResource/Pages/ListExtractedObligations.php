<?php

namespace App\Filament\Resources\ExtractedObligationResource\Pages;

use App\Filament\Resources\ExtractedObligationResource;
use Filament\Resources\Pages\ListRecords;

class ListExtractedObligations extends ListRecords
{
    protected static string $resource = ExtractedObligationResource::class;

    /** Default rows per page — keeps the list lightweight for large datasets. */
    protected string|int|null $defaultTableRecordsPerPageSelectOption = 25;
}
