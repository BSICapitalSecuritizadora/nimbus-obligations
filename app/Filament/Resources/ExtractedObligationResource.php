<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExtractedObligationResource\Pages;
use App\Models\ExtractedObligation;
use App\Models\Obligation;
use App\Models\ObligationHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExtractedObligationResource extends Resource
{
    protected static ?string $model = ExtractedObligation::class;

    protected static ?string $navigationIcon  = 'heroicon-o-light-bulb';
    protected static ?string $navigationGroup = 'Obrigações';
    protected static ?string $navigationLabel = 'Obrigações Sugeridas';
    protected static ?string $modelLabel      = 'Obrigação Sugerida';
    protected static ?string $pluralModelLabel = 'Obrigações Sugeridas';
    protected static ?int $navigationSort     = 20;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'suggested')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')->columns(2)->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Select::make('obligation_type')
                    ->label('Tipo de Obrigação')
                    ->options(array_combine(
                        \App\Models\Obligation::obligationTypes(),
                        \App\Models\Obligation::obligationTypes(),
                    ))
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('priority')
                    ->label('Prioridade')
                    ->options(ExtractedObligation::priorityOptions())
                    ->required()
                    ->default('medium'),
            ]),

            Forms\Components\Section::make('Detalhes')->columns(2)->schema([
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('responsible_area')->label('Área Responsável'),
                Forms\Components\TextInput::make('responsible_party')->label('Responsável'),
                Forms\Components\Select::make('recurrence')
                    ->label('Periodicidade')
                    ->options([
                        'Mensal' => 'Mensal', 'Bimestral' => 'Bimestral',
                        'Trimestral' => 'Trimestral', 'Semestral' => 'Semestral',
                        'Anual' => 'Anual', 'Eventual' => 'Eventual',
                    ]),
                Forms\Components\TextInput::make('due_rule')->label('Regra de Vencimento'),
                Forms\Components\DatePicker::make('due_date')->label('Data de Vencimento')->displayFormat('d/m/Y'),
                Forms\Components\Textarea::make('required_evidence')->label('Evidência Exigida')->rows(2),
            ]),

            Forms\Components\Section::make('Origem no Termo')->columns(2)->schema([
                Forms\Components\TextInput::make('source_clause')->label('Referência no Termo'),
                Forms\Components\TextInput::make('source_page')->label('Página')->numeric(),
                Forms\Components\Textarea::make('source_excerpt')->label('Trecho de Origem')->rows(3)->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Revisão')->schema([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(ExtractedObligation::statusOptions())
                    ->required()
                    ->default('suggested'),
                Forms\Components\Textarea::make('review_notes')->label('Notas de Revisão')->rows(2),
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
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('obligation_type')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('responsible_area')
                    ->label('Área')
                    ->placeholder('—'),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Prioridade')
                    ->formatStateUsing(fn ($state) => ExtractedObligation::priorityOptions()[$state] ?? $state)
                    ->colors([
                        'gray'    => 'low',
                        'info'    => 'medium',
                        'warning' => 'high',
                        'danger'  => 'critical',
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => ExtractedObligation::statusOptions()[$state] ?? $state)
                    ->colors([
                        'warning' => 'suggested',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                        'info'    => 'needs_review',
                    ]),

                Tables\Columns\TextColumn::make('confidence_score')
                    ->label('Confiança')
                    ->formatStateUsing(fn ($state) => $state ? round($state * 100).'%' : '—')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 0.80 => 'success',
                        $state >= 0.60 => 'warning',
                        default        => 'danger',
                    }),

                Tables\Columns\TextColumn::make('source_clause')
                    ->label('Referência no Termo')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('operation')
                    ->label('Operação')
                    ->relationship('operation', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(ExtractedObligation::statusOptions()),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridade')
                    ->options(ExtractedObligation::priorityOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprovar Obrigação')
                    ->modalDescription('Esta sugestão será convertida em uma obrigação ativa. Confirma?')
                    ->action(function (ExtractedObligation $record) {
                        $obligation = Obligation::create([
                            'operation_id'           => $record->operation_id,
                            'extracted_obligation_id' => $record->id,
                            'title'                  => $record->title,
                            'obligation_type'         => $record->obligation_type,
                            'description'            => $record->description,
                            'responsible_party'       => $record->responsible_party,
                            'responsible_area'        => $record->responsible_area,
                            'recurrence'             => $record->recurrence,
                            'due_rule'               => $record->due_rule,
                            'due_date'               => $record->due_date,
                            'priority'               => $record->priority,
                            'status'                 => 'on_track',
                            'required_evidence'       => $record->required_evidence,
                            'source_clause'          => $record->source_clause,
                            'source_page'            => $record->source_page,
                            'source_excerpt'         => $record->source_excerpt,
                        ]);

                        ObligationHistory::create([
                            'obligation_id' => $obligation->id,
                            'action'        => 'Obrigação criada a partir de sugestão aprovada.',
                            'new_value'     => 'on_track',
                        ]);

                        $record->update([
                            'status'      => 'approved',
                            'reviewed_by' => auth()->user()?->name ?? 'Sistema',
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()->title('Obrigação aprovada e criada!')->success()->send();
                    })
                    ->visible(fn (ExtractedObligation $r) => in_array($r->status, ['suggested', 'needs_review'])),

                Tables\Actions\Action::make('reject')
                    ->label('Rejeitar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (ExtractedObligation $record) {
                        $record->update([
                            'status'      => 'rejected',
                            'reviewed_by' => auth()->user()?->name ?? 'Sistema',
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->title('Sugestão rejeitada.')->warning()->send();
                    })
                    ->visible(fn (ExtractedObligation $r) => in_array($r->status, ['suggested', 'needs_review'])),

                Tables\Actions\EditAction::make()->label('Editar'),
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
            'index'  => Pages\ListExtractedObligations::route('/'),
            'view'   => Pages\ViewExtractedObligation::route('/{record}'),
            'edit'   => Pages\EditExtractedObligation::route('/{record}/edit'),
        ];
    }
}
