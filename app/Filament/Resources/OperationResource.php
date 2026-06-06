<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OperationResource\Pages;
use App\Models\Operation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OperationResource extends Resource
{
    protected static ?string $model = Operation::class;

    protected static ?string $navigationIcon  = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Operações';
    protected static ?string $navigationLabel = 'Operações';
    protected static ?string $modelLabel      = 'Operação';
    protected static ?string $pluralModelLabel = 'Operações';
    protected static ?int $navigationSort     = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome da Operação')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options(Operation::typeOptions())
                        ->required()
                        ->default('CRI'),

                    Forms\Components\TextInput::make('series')
                        ->label('Série')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('if_code')
                        ->label('Código IF (CETIP/B3)')
                        ->maxLength(100),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(Operation::statusOptions())
                        ->required()
                        ->default('active'),
                ]),

            Forms\Components\Section::make('Partes')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('issuer')
                        ->label('Emissora / Securitizadora')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('debtor')
                        ->label('Devedor')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('assignor')
                        ->label('Cedente')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('fiduciary_agent')
                        ->label('Agente Fiduciário')
                        ->maxLength(255),
                ]),

            Forms\Components\Section::make('Datas')
                ->columns(2)
                ->schema([
                    Forms\Components\DatePicker::make('issue_date')
                        ->label('Data de Emissão')
                        ->displayFormat('d/m/Y'),

                    Forms\Components\DatePicker::make('maturity_date')
                        ->label('Data de Vencimento')
                        ->displayFormat('d/m/Y'),
                ]),

            Forms\Components\Section::make('Observações')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Observações')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'info'    => 'CRI',
                        'success' => 'CRA',
                        'warning' => 'Debenture',
                        'primary' => 'Nota Comercial',
                        'gray'    => 'Other',
                    ]),

                Tables\Columns\TextColumn::make('series')
                    ->label('Série')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('issuer')
                    ->label('Emissora')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('fiduciary_agent')
                    ->label('Agente Fiduciário')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Emissão')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('maturity_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => Operation::statusOptions()[$state] ?? $state)
                    ->colors([
                        'success' => 'active',
                        'gray'    => 'draft',
                        'danger'  => 'closed',
                    ]),

                Tables\Columns\TextColumn::make('obligations_count')
                    ->label('Obrigações')
                    ->counts('obligations')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('term_documents_count')
                    ->label('Documentos')
                    ->counts('termDocuments')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(Operation::typeOptions()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Operation::statusOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('overview')
                    ->label('Visão da Operação')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('primary')
                    ->url(fn (Operation $record) => static::getUrl('overview', ['record' => $record])),
                Tables\Actions\ViewAction::make()->label('Ver'),
                Tables\Actions\EditAction::make()->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Excluir'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'    => Pages\ListOperations::route('/'),
            'create'   => Pages\CreateOperation::route('/create'),
            'view'     => Pages\ViewOperation::route('/{record}'),
            'edit'     => Pages\EditOperation::route('/{record}/edit'),
            'overview' => Pages\OperationOverview::route('/{record}/overview'),
        ];
    }
}
