<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ObligationResource\Pages;
use App\Models\Obligation;
use App\Models\ObligationHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ObligationResource extends Resource
{
    protected static ?string $model = Obligation::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Obrigações';
    protected static ?string $navigationLabel = 'Obrigações Aprovadas';
    protected static ?string $modelLabel      = 'Obrigação';
    protected static ?string $pluralModelLabel = 'Obrigações';
    protected static ?int $navigationSort     = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')->columns(2)->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Select::make('operation_id')
                    ->label('Operação')
                    ->relationship('operation', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('obligation_type')
                    ->label('Tipo de Obrigação')
                    ->options(array_combine(Obligation::obligationTypes(), Obligation::obligationTypes()))
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('priority')
                    ->label('Prioridade')
                    ->options(Obligation::priorityOptions())
                    ->required()
                    ->default('medium'),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(Obligation::statusOptions())
                    ->required()
                    ->default('on_track'),
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
                Forms\Components\Textarea::make('required_evidence')->label('Evidência Exigida')->rows(2)->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Origem no Termo')->columns(2)->schema([
                Forms\Components\TextInput::make('source_clause')->label('Referência no Termo'),
                Forms\Components\TextInput::make('source_page')->label('Página')->numeric(),
                Forms\Components\Textarea::make('source_excerpt')->label('Trecho de Origem')->rows(3)->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Observações')->schema([
                Forms\Components\Textarea::make('notes')->label('Observações')->rows(3)->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => Obligation::statusOptions()[$state] ?? $state)
                    ->colors([
                        'success' => 'on_track',
                        'warning' => 'due_soon',
                        'danger'  => 'overdue',
                        'gray'    => 'completed',
                        'info'    => 'under_review',
                    ]),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Prioridade')
                    ->formatStateUsing(fn ($state) => Obligation::priorityOptions()[$state] ?? $state)
                    ->colors([
                        'gray'    => 'low',
                        'info'    => 'medium',
                        'warning' => 'high',
                        'danger'  => 'critical',
                    ]),

                Tables\Columns\TextColumn::make('source_clause')
                    ->label('Referência no Termo')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('operation')
                    ->label('Operação')
                    ->relationship('operation', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Obligation::statusOptions()),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridade')
                    ->options(Obligation::priorityOptions()),

                Tables\Filters\SelectFilter::make('obligation_type')
                    ->label('Tipo')
                    ->options(array_combine(Obligation::obligationTypes(), Obligation::obligationTypes())),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_completed')
                    ->label('Concluir')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Obligation $record) {
                        $old = $record->status;
                        $record->update(['status' => 'completed']);
                        ObligationHistory::create([
                            'obligation_id' => $record->id,
                            'action'        => 'Status alterado para Concluída.',
                            'old_value'     => $old,
                            'new_value'     => 'completed',
                        ]);
                        Notification::make()->title('Obrigação marcada como concluída.')->success()->send();
                    })
                    ->visible(fn (Obligation $r) => $r->status !== 'completed'),

                Tables\Actions\ViewAction::make()->label('Ver'),
                Tables\Actions\EditAction::make()->label('Editar'),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListObligations::route('/'),
            'create' => Pages\CreateObligation::route('/create'),
            'view'   => Pages\ViewObligation::route('/{record}'),
            'edit'   => Pages\EditObligation::route('/{record}/edit'),
        ];
    }
}
