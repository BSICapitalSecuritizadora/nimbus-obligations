<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TermDocumentResource\Pages;
use App\Jobs\GenerateTermDocumentObligationsJob;
use App\Jobs\ProcessTermDocumentJob;
use App\Models\ExtractedObligation;
use App\Models\TermDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TermDocumentResource extends Resource
{
    protected static ?string $model = TermDocument::class;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Documentos';
    protected static ?string $navigationLabel = 'Termos de Securitização';
    protected static ?string $modelLabel      = 'Termo de Securitização';
    protected static ?string $pluralModelLabel = 'Termos de Securitização';
    protected static ?int $navigationSort     = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Documento')->schema([
                Forms\Components\Select::make('operation_id')
                    ->label('Operação')
                    ->relationship('operation', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\FileUpload::make('stored_path')
                    ->label('Arquivo PDF do Termo de Securitização')
                    ->disk('local')
                    ->directory('term-documents')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(20480) // 20 MB
                    ->required()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            // FileUpload stores the path; capture the original filename
                            if (is_string($state)) {
                                $set('original_filename', basename($state));
                            }
                        }
                    })
                    ->helperText('Apenas arquivos PDF. Tamanho máximo: 20 MB.'),

                Forms\Components\Hidden::make('original_filename'),
                Forms\Components\Hidden::make('mime_type')->default('application/pdf'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('operation.name')
                    ->label('Operação')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('original_filename')
                    ->label('Arquivo')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('processing_status')
                    ->label('Status do Processamento')
                    ->formatStateUsing(fn ($state) => TermDocument::processingStatusOptions()[$state] ?? $state)
                    ->colors([
                        'gray'    => 'pending',
                        'warning' => 'processing',
                        'success' => 'processed',
                        'danger'  => 'failed',
                    ]),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processado em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('extracted_obligations_count')
                    ->label('Sugestões')
                    ->counts('extractedObligations')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('obligation_generation_status')
                    ->label('Status IA')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? (TermDocument::obligationGenerationStatusOptions()[$state] ?? $state) : '—')
                    ->color(fn ($state) => match ($state) {
                        'queued'     => 'gray',
                        'processing' => 'warning',
                        'completed'  => 'success',
                        'failed'     => 'danger',
                        default      => 'gray',
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('extraction_provider')
                    ->label('Provedor IA')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'gemini' => 'primary',
                        'mock'   => 'gray',
                        default  => 'gray',
                    })
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('extraction_model')
                    ->label('Modelo IA')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('obligation_chunks_processed')
                    ->label('Chunks IA')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('obligation_suggestions_created')
                    ->label('Criadas IA')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('obligation_skipped_items')
                    ->label('Descartadas IA')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('obligation_extraction_error')
                    ->label('Erro IA')
                    ->limit(80)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Upload em')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('operation')
                    ->label('Operação')
                    ->relationship('operation', 'name'),

                Tables\Filters\SelectFilter::make('processing_status')
                    ->label('Status')
                    ->options(TermDocument::processingStatusOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('process')
                    ->label('Processar Documento')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (TermDocument $record) {
                        ProcessTermDocumentJob::dispatch($record);
                        Notification::make()
                            ->title('Processamento do documento iniciado em segundo plano.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (TermDocument $record) => in_array($record->processing_status, ['pending', 'failed'])),

                Tables\Actions\Action::make('generate_obligations')
                    ->label('Gerar Obrigações')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Gerar Obrigações Sugeridas')
                    ->modalDescription('Isso irá analisar o texto extraído e gerar sugestões de obrigações para revisão. Sugestões anteriores ainda não aprovadas serão removidas.')
                    ->action(function (TermDocument $record) {
                        static::queueObligationGeneration($record);

                        Notification::make()
                            ->title('Geração de obrigações iniciada em segundo plano.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (TermDocument $record) => $record->isProcessed()),

                Tables\Actions\ViewAction::make()->label('Ver'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function queueObligationGeneration(TermDocument $record): void
    {
        $document = TermDocument::query()->findOrFail($record->getKey());

        // Clear non-approved suggestions so the UI shows a clean slate immediately.
        ExtractedObligation::query()
            ->where('term_document_id', $document->id)
            ->whereIn('status', ['suggested', 'needs_review'])
            ->delete();

        // Reset to a fully clean metadata record — never merge with stale previous runs.
        $document->update([
            'extraction_provider' => config('obligations.extractor'),
            'extraction_model'    => static::configuredExtractorModel(),
            'extraction_error'    => null,
            'extraction_metadata' => [
                'generation_status'         => 'queued',
                'queued_at'                 => now()->toIso8601String(),
                'started_at'                => null,
                'finished_at'               => null,
                'last_error'                => null,
                // chunk / selection counters (filled in by the extractor)
                'total_chunks_available'    => 0,
                'chunks_selected'           => 0,
                'chunks_processed'          => 0,
                'chunk_selection_mode'      => config('obligations.gemini.chunk_selection_mode', 'all'),
                'max_chunks_limit'          => config('obligations.gemini.max_chunks_per_document'),
                'gemini_api_key_configured' => filled(config('obligations.gemini.api_key')),
                // obligation counters (filled in by the extractor / service)
                'suggestions_generated'     => 0,
                'obligations_returned'      => 0,
                'obligations_created'       => 0,
                'obligations_skipped'       => 0,
                'skipped_reasons'           => [],
            ],
        ]);

        GenerateTermDocumentObligationsJob::dispatch($document->id);
    }

    private static function configuredExtractorModel(): ?string
    {
        return match (config('obligations.extractor')) {
            'gemini' => config('obligations.gemini.model'),
            'mock'   => 'keyword-patterns',
            default  => null,
        };
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTermDocuments::route('/'),
            'create' => Pages\CreateTermDocument::route('/create'),
            'view'   => Pages\ViewTermDocument::route('/{record}'),
        ];
    }
}
