<?php

namespace App\Filament\Resources\TermDocumentResource\Pages;

use App\Filament\Resources\TermDocumentResource;
use App\Jobs\ProcessTermDocumentJob;
use App\Models\TermDocument;
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
                    ProcessTermDocumentJob::dispatch($this->record);
                    Notification::make()->title('Processamento do documento iniciado em segundo plano.')->success()->send();
                })
                ->visible(fn () => in_array($this->record->processing_status, ['pending', 'failed'])),

            Actions\Action::make('generate_obligations')
                ->label('Gerar Obrigações Sugeridas')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Gerar Obrigações Sugeridas')
                ->modalDescription('Isso irá analisar o texto extraído e gerar sugestões de obrigações para revisão. Sugestões anteriores (não aprovadas) serão removidas. O processo continuará em segundo plano.')
                ->action(function () {
                    TermDocumentResource::queueObligationGeneration($this->record);
                    $this->record->refresh();
                    $this->refreshFormData([
                        'extraction_metadata',
                        'extraction_provider',
                        'extraction_model',
                        'extraction_error',
                    ]);

                    Notification::make()
                        ->title('Geração de obrigações iniciada em segundo plano.')
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

            Infolists\Components\Section::make('Extração de Obrigações por IA')
                ->columns(3)
                ->collapsed()
                ->schema([
                    // ── Status row ────────────────────────────────────────────
                    Infolists\Components\TextEntry::make('extraction_metadata.generation_status')
                        ->label('Status da Geração')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'queued'     => 'Na fila',
                            'processing' => 'Processando',
                            'completed'  => 'Concluído',
                            'failed'     => 'Falhou',
                            'generating' => 'Processando',
                            'done'       => 'Concluído',
                            default      => '—',
                        })
                        ->color(fn ($state) => match ($state) {
                            'queued'     => 'gray',
                            'processing' => 'warning',
                            'completed'  => 'success',
                            'failed'     => 'danger',
                            'generating' => 'warning',
                            'done'       => 'success',
                            default      => 'gray',
                        })
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('extraction_provider')
                        ->label('Provedor')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'gemini' => 'primary',
                            'mock'   => 'gray',
                            default  => 'gray',
                        })
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('extraction_model')
                        ->label('Modelo')
                        ->placeholder('—'),

                    // ── API key / config ───────────────────────────────────────
                    Infolists\Components\TextEntry::make('extraction_metadata.gemini_api_key_configured')
                        ->label('API Key Configurada')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match (true) {
                            $state === true  => 'Sim',
                            $state === false => 'NÃO — configure GEMINI_API_KEY',
                            default          => '—',
                        })
                        ->color(fn ($state) => $state === false ? 'danger' : 'success')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('extraction_metadata.chunk_selection_mode')
                        ->label('Modo de Seleção')
                        ->badge()
                        ->color('gray')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('extraction_metadata.max_chunks_limit')
                        ->label('Limite de Chunks')
                        ->formatStateUsing(fn ($state) => $state !== null ? (string) $state : 'Sem limite')
                        ->placeholder('Sem limite'),

                    // ── Chunk counters ─────────────────────────────────────────
                    Infolists\Components\TextEntry::make('extraction_metadata.total_chunks_available')
                        ->label('Chunks Disponíveis')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('extraction_metadata.chunks_selected')
                        ->label('Chunks Selecionados')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('extraction_metadata.chunks_processed')
                        ->label('Chunks Processados')
                        ->placeholder('—'),

                    // ── Obligation counters ────────────────────────────────────
                    Infolists\Components\TextEntry::make('extraction_metadata.obligations_returned')
                        ->label('Retornadas pela IA')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('extraction_metadata.obligations_created')
                        ->label('Criadas (após dedup.)')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('extraction_metadata.suggestions_generated')
                        ->label('Sugestões Salvas')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('extraction_metadata.obligations_skipped')
                        ->label('Descartadas (validação)')
                        ->placeholder('—'),

                    // ── Timestamps ────────────────────────────────────────────
                    Infolists\Components\TextEntry::make('extraction_metadata.started_at')
                        ->label('Iniciado em')
                        ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i:s') : '—')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('extraction_metadata.finished_at')
                        ->label('Concluído em')
                        ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i:s') : '—')
                        ->placeholder('—'),

                    // ── Errors ────────────────────────────────────────────────
                    Infolists\Components\TextEntry::make('extraction_metadata.last_error')
                        ->label('Erro na Extração IA')
                        ->color('danger')
                        ->placeholder('Nenhum')
                        ->columnSpanFull(),

                    Infolists\Components\TextEntry::make('extraction_metadata.skipped_reasons')
                        ->label('Obrigações Descartadas — Motivos')
                        ->formatStateUsing(function ($state) {
                            if (empty($state)) {
                                return null;
                            }
                            $lines = [];
                            foreach ((array) $state as $item) {
                                $reason  = $item['reason'] ?? '?';
                                $title   = $item['title'] ?? '—';
                                $detail  = $item['detail'] ?? null;
                                $preview = $item['source_excerpt_preview'] ?? null;
                                $line    = "[{$reason}] {$title}";
                                if ($detail) {
                                    $line .= "\n  Detalhe: {$detail}";
                                }
                                if ($preview) {
                                    $line .= "\n  Trecho: «{$preview}»";
                                }
                                $lines[] = $line;
                            }
                            return implode("\n\n", $lines);
                        })
                        ->placeholder('Nenhuma obrigação descartada')
                        ->columnSpanFull(),
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
