<?php

namespace App\Filament\Resources\TermDocumentResource\Pages;

use App\Filament\Resources\TermDocumentResource;
use App\Jobs\ProcessTermDocumentJob;
use App\Models\TermDocument;
use App\Services\ObligationExtractionService;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTermDocument extends ViewRecord
{
    protected static string $resource = TermDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('process')
                ->label('Processar Documento')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    ProcessTermDocumentJob::dispatchSync($this->record);
                    $this->refreshFormData(['processing_status', 'extracted_text', 'processed_at']);
                    Notification::make()->title('Documento processado!')->success()->send();
                })
                ->visible(fn () => in_array($this->record->processing_status, ['pending', 'failed'])),

            Actions\Action::make('generate_obligations')
                ->label('Gerar Obrigações Sugeridas')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Gerar Obrigações Sugeridas')
                ->modalDescription('Isso irá analisar o texto extraído e gerar sugestões de obrigações para revisão. Sugestões anteriores (não aprovadas) serão removidas.')
                ->action(function () {
                    $service = app(ObligationExtractionService::class);
                    $count   = $service->extractAndSave($this->record);
                    Notification::make()
                        ->title("$count obrigações sugeridas geradas!")
                        ->body('Acesse "Obrigações Sugeridas" para revisar e aprovar.')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->isProcessed()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Informações do Arquivo')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('operation.name')->label('Operação'),
                    Infolists\Components\TextEntry::make('original_filename')->label('Nome do Arquivo'),
                    Infolists\Components\TextEntry::make('processing_status')
                        ->label('Status do Processamento')
                        ->formatStateUsing(fn ($state) => TermDocument::processingStatusOptions()[$state] ?? $state)
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'pending'    => 'gray',
                            'processing' => 'warning',
                            'processed'  => 'success',
                            'failed'     => 'danger',
                            default      => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('uploaded_by')->label('Enviado por')->placeholder('—'),
                    Infolists\Components\TextEntry::make('processed_at')->label('Processado em')->dateTime('d/m/Y H:i')->placeholder('—'),
                    Infolists\Components\TextEntry::make('extraction_error')->label('Erro de Extração')->placeholder('Nenhum')->color('danger'),
                ]),

            Infolists\Components\Section::make('Texto Extraído')
                ->collapsed()
                ->schema([
                    Infolists\Components\TextEntry::make('extracted_text')
                        ->label('Prévia do Texto')
                        ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 3000).(strlen($state) > 3000 ? '...' : '') : 'Nenhum texto extraído.')
                        ->prose()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
