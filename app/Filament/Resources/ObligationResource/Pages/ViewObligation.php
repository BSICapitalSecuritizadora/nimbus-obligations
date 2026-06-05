<?php

namespace App\Filament\Resources\ObligationResource\Pages;

use App\Filament\Resources\ObligationResource;
use App\Models\Obligation;
use App\Models\ObligationHistory;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewObligation extends ViewRecord
{
    protected static string $resource = ObligationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_completed')
                ->label('Marcar como Concluída')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $old = $this->record->status;
                    $this->record->update(['status' => 'completed']);
                    ObligationHistory::create([
                        'obligation_id' => $this->record->id,
                        'action'        => 'Obrigação concluída.',
                        'old_value'     => $old,
                        'new_value'     => 'completed',
                    ]);
                    Notification::make()->title('Obrigação concluída!')->success()->send();
                })
                ->visible(fn () => $this->record->status !== 'completed'),

            Actions\EditAction::make()->label('Editar'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Identificação')->columns(2)->schema([
                Infolists\Components\TextEntry::make('title')->label('Título')->columnSpanFull()->weight('bold'),
                Infolists\Components\TextEntry::make('operation.name')->label('Operação'),
                Infolists\Components\TextEntry::make('obligation_type')->label('Tipo')->badge()->color('gray'),
                Infolists\Components\TextEntry::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => Obligation::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'on_track'     => 'success',
                        'due_soon'     => 'warning',
                        'overdue'      => 'danger',
                        'completed'    => 'gray',
                        'under_review' => 'info',
                        default        => 'gray',
                    }),
                Infolists\Components\TextEntry::make('priority')
                    ->label('Prioridade')
                    ->formatStateUsing(fn ($state) => Obligation::priorityOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'low'      => 'gray',
                        'medium'   => 'info',
                        'high'     => 'warning',
                        'critical' => 'danger',
                        default    => 'gray',
                    }),
            ]),

            Infolists\Components\Section::make('Detalhes')->columns(2)->schema([
                Infolists\Components\TextEntry::make('description')->label('Descrição')->columnSpanFull()->prose(),
                Infolists\Components\TextEntry::make('responsible_area')->label('Área Responsável')->placeholder('—'),
                Infolists\Components\TextEntry::make('responsible_party')->label('Responsável')->placeholder('—'),
                Infolists\Components\TextEntry::make('recurrence')->label('Periodicidade')->placeholder('—'),
                Infolists\Components\TextEntry::make('due_rule')->label('Regra de Vencimento')->placeholder('—'),
                Infolists\Components\TextEntry::make('due_date')->label('Data de Vencimento')->date('d/m/Y')->placeholder('—'),
                Infolists\Components\TextEntry::make('required_evidence')->label('Evidência Exigida')->placeholder('—')->columnSpanFull(),
            ]),

            Infolists\Components\Section::make('Origem no Termo')->columns(2)->schema([
                Infolists\Components\TextEntry::make('source_clause')->label('Referência no Termo')->placeholder('—'),
                Infolists\Components\TextEntry::make('source_page')->label('Página')->placeholder('—'),
                Infolists\Components\TextEntry::make('source_excerpt')->label('Trecho de Origem')->placeholder('—')->columnSpanFull()->prose(),
            ]),

            Infolists\Components\Section::make('Observações')->schema([
                Infolists\Components\TextEntry::make('notes')->label('Observações')->placeholder('—')->columnSpanFull(),
            ]),

            Infolists\Components\Section::make('Histórico')
                ->collapsed()
                ->schema([
                    Infolists\Components\RepeatableEntry::make('histories')
                        ->label('')
                        ->schema([
                            Infolists\Components\TextEntry::make('created_at')->label('Data')->dateTime('d/m/Y H:i'),
                            Infolists\Components\TextEntry::make('action')->label('Ação'),
                            Infolists\Components\TextEntry::make('old_value')->label('De')->placeholder('—'),
                            Infolists\Components\TextEntry::make('new_value')->label('Para')->placeholder('—'),
                        ])
                        ->columns(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
