<?php

namespace App\Filament\Resources\ExtractedObligationResource\Pages;

use App\Filament\Resources\ExtractedObligationResource;
use App\Models\ExtractedObligation;
use App\Models\Obligation;
use App\Models\ObligationHistory;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewExtractedObligation extends ViewRecord
{
    protected static string $resource = ExtractedObligationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Aprovar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $record = $this->record;

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
                    $this->redirect(ExtractedObligationResource::getUrl('index'));
                })
                ->visible(fn () => in_array($this->record->status, ['suggested', 'needs_review'])),

            Actions\Action::make('reject')
                ->label('Rejeitar')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status'      => 'rejected',
                        'reviewed_by' => auth()->user()?->name ?? 'Sistema',
                        'reviewed_at' => now(),
                    ]);
                    Notification::make()->title('Sugestão rejeitada.')->warning()->send();
                })
                ->visible(fn () => in_array($this->record->status, ['suggested', 'needs_review'])),

            Actions\EditAction::make()->label('Editar'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Sugestão')->columns(2)->schema([
                Infolists\Components\TextEntry::make('title')->label('Título')->columnSpanFull()->weight('bold'),
                Infolists\Components\TextEntry::make('operation.name')->label('Operação'),
                Infolists\Components\TextEntry::make('obligation_type')->label('Tipo')->badge()->color('gray'),
                Infolists\Components\TextEntry::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => ExtractedObligation::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        'suggested' => 'warning',
                        default     => 'info',
                    }),
                Infolists\Components\TextEntry::make('priority')
                    ->label('Prioridade')
                    ->formatStateUsing(fn ($state) => ExtractedObligation::priorityOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'low'      => 'gray',
                        'medium'   => 'info',
                        'high'     => 'warning',
                        'critical' => 'danger',
                        default    => 'gray',
                    }),
                Infolists\Components\TextEntry::make('confidence_score')
                    ->label('Pontuação de Confiança')
                    ->formatStateUsing(fn ($state) => $state ? round($state * 100).'%' : '—'),
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
                Infolists\Components\TextEntry::make('source_excerpt')
                    ->label('Trecho de Origem')
                    ->columnSpanFull()
                    ->prose()
                    ->placeholder('—'),
            ]),

            Infolists\Components\Section::make('Revisão')->columns(2)->schema([
                Infolists\Components\TextEntry::make('reviewed_by')->label('Revisado por')->placeholder('—'),
                Infolists\Components\TextEntry::make('reviewed_at')->label('Revisado em')->dateTime('d/m/Y H:i')->placeholder('—'),
                Infolists\Components\TextEntry::make('review_notes')->label('Notas de Revisão')->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }
}
