<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TermDocumentResource\Pages;
use App\Jobs\ProcessTermDocumentJob;
use App\Models\ExtractedObligation;
use App\Models\Operation;
use App\Models\TermDocument;
use App\Services\ObligationExtractionService;
use App\Services\TermDocumentTextExtractor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

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
                        ProcessTermDocumentJob::dispatchSync($record);
                        Notification::make()
                            ->title('Documento processado com sucesso!')
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
                        $service = app(ObligationExtractionService::class);
                        $count   = $service->extractAndSave($record);
                        Notification::make()
                            ->title("$count obrigações sugeridas geradas!")
                            ->body('Acesse "Obrigações Sugeridas" para revisar.')
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTermDocuments::route('/'),
            'create' => Pages\CreateTermDocument::route('/create'),
            'view'   => Pages\ViewTermDocument::route('/{record}'),
        ];
    }
}
