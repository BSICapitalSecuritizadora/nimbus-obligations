<?php

namespace App\Filament\Resources\TermDocumentResource\Pages;

use App\Filament\Resources\TermDocumentResource;
use App\Models\TermDocument;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateTermDocument extends CreateRecord
{
    protected static string $resource = TermDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // The FileUpload component stores the path; capture size and filename
        $path = $data['stored_path'] ?? null;

        if ($path && Storage::disk('local')->exists($path)) {
            $data['file_size']         = Storage::disk('local')->size($path);
            $data['file_hash']         = md5_file(Storage::disk('local')->path($path));
            $data['original_filename'] = basename($path);
        }

        $data['mime_type']  = 'application/pdf';
        $data['uploaded_by'] = auth()->user()?->name ?? 'Sistema';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
