<?php

namespace App\Filament\Resources\OperationResource\Pages;

use App\Filament\Resources\OperationResource;
use App\Filament\Resources\TermDocumentResource;
use App\Filament\Resources\ExtractedObligationResource;
use App\Filament\Resources\ObligationResource;
use App\Models\ExtractedObligation;
use App\Models\Obligation;
use App\Models\Operation;
use App\Models\TermDocument;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViewOperation extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = OperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('overview')
                ->label('Visão da Operação')
                ->icon('heroicon-o-chart-bar-square')
                ->color('primary')
                ->url(fn () => OperationResource::getUrl('overview', ['record' => $this->record])),

            Actions\EditAction::make()->label('Editar'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Identificação')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('name')->label('Nome'),
                    Infolists\Components\TextEntry::make('type')->label('Tipo')
                        ->formatStateUsing(fn ($state) => Operation::typeOptions()[$state] ?? $state)
                        ->badge(),
                    Infolists\Components\TextEntry::make('series')->label('Série')->placeholder('—'),
                    Infolists\Components\TextEntry::make('if_code')->label('Código IF')->placeholder('—'),
                    Infolists\Components\TextEntry::make('status')->label('Status')
                        ->formatStateUsing(fn ($state) => Operation::statusOptions()[$state] ?? $state)
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'active' => 'success',
                            'draft'  => 'gray',
                            'closed' => 'danger',
                            default  => 'gray',
                        }),
                ]),

            Infolists\Components\Section::make('Partes')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('issuer')->label('Emissora / Securitizadora')->placeholder('—'),
                    Infolists\Components\TextEntry::make('debtor')->label('Devedor')->placeholder('—'),
                    Infolists\Components\TextEntry::make('assignor')->label('Cedente')->placeholder('—'),
                    Infolists\Components\TextEntry::make('fiduciary_agent')->label('Agente Fiduciário')->placeholder('—'),
                ]),

            Infolists\Components\Section::make('Datas')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('issue_date')->label('Emissão')->date('d/m/Y')->placeholder('—'),
                    Infolists\Components\TextEntry::make('maturity_date')->label('Vencimento')->date('d/m/Y')->placeholder('—'),
                ]),

            Infolists\Components\Section::make('Observações')
                ->schema([
                    Infolists\Components\TextEntry::make('notes')->label('Observações')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(TermDocument::query()->where('operation_id', $this->record->id))
            ->heading('Termos de Securitização')
            ->columns([
                Tables\Columns\TextColumn::make('original_filename')->label('Arquivo'),
                Tables\Columns\BadgeColumn::make('processing_status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => TermDocument::processingStatusOptions()[$state] ?? $state)
                    ->colors([
                        'gray'    => 'pending',
                        'warning' => 'processing',
                        'success' => 'processed',
                        'danger'  => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label('Enviado em')->date('d/m/Y H:i'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('upload')
                    ->label('Fazer Upload de Termo')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->url(fn () => TermDocumentResource::getUrl('create', ['operation_id' => $this->record->id])),
            ])
            ->actions([
                Tables\Actions\Action::make('view_doc')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (TermDocument $record) => TermDocumentResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
