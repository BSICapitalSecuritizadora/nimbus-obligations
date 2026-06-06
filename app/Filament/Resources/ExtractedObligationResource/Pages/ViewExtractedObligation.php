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
                ->label(fn () => $this->hasWarnings($this->record) ? 'Aprovar (com avisos)' : 'Aprovar')
                ->icon('heroicon-o-check-circle')
                ->color(fn () => $this->hasWarnings($this->record) ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->hasWarnings($this->record)
                    ? 'Aprovar sugestão com avisos de validação?'
                    : 'Aprovar sugestão?'
                )
                ->modalDescription(fn () => $this->hasWarnings($this->record)
                    ? 'ATENÇÃO: Esta sugestão possui avisos de validação (baixa confiança, prazo sem respaldo no trecho ou cláusula de origem não identificada). Verifique o prazo e o trecho de origem antes de prosseguir.'
                    : 'Isso criará uma obrigação ativa a partir desta sugestão.'
                )
                ->action(function () {
                    $record = $this->record;

                    $obligation = Obligation::create([
                        'operation_id'            => $record->operation_id,
                        'extracted_obligation_id' => $record->id,
                        'title'                   => $record->title,
                        'obligation_type'         => $record->obligation_type,
                        'description'             => $record->description,
                        'responsible_party'       => $record->responsible_party,
                        'responsible_area'        => $record->responsible_area,
                        'recurrence'              => $record->recurrence,
                        'due_rule'                => $record->due_rule,
                        'due_date'                => $record->due_date,
                        'priority'                => $record->priority,
                        'status'                  => 'on_track',
                        'required_evidence'       => $record->required_evidence,
                        'source_clause'           => $record->source_clause,
                        'source_page'             => $record->source_page,
                        'source_excerpt'          => $record->source_excerpt,
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

    /** Returns true if the record has any quality flags that warrant reviewer attention. */
    private function hasWarnings(ExtractedObligation $record): bool
    {
        return ($record->confidence_score !== null && $record->confidence_score < 0.60)
            || empty($record->source_clause)
            || empty($record->due_rule)
            || empty($record->responsible_party)
            || ! empty($record->review_notes);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            // ── Warning banner (conditional) ──────────────────────────────────
            Infolists\Components\View::make('filament.infolists.components.extraction-warning')
                ->visible(fn (ExtractedObligation $record) => $this->hasWarnings($record))
                ->columnSpanFull(),

            // ── Status & classification ───────────────────────────────────────
            Infolists\Components\Section::make('Identificação')->columns(3)->schema([
                Infolists\Components\TextEntry::make('title')
                    ->label('Título')
                    ->weight('bold')
                    ->columnSpanFull(),

                Infolists\Components\TextEntry::make('operation.name')
                    ->label('Operação'),

                Infolists\Components\TextEntry::make('obligation_type')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray'),

                Infolists\Components\TextEntry::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => ExtractedObligation::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved'     => 'success',
                        'rejected'     => 'danger',
                        'suggested'    => 'warning',
                        'needs_review' => 'info',
                        default        => 'gray',
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
            ]),

            // ── Obligation details ────────────────────────────────────────────
            Infolists\Components\Section::make('Obrigação')->columns(2)->schema([
                Infolists\Components\TextEntry::make('description')
                    ->label('Descrição')
                    ->prose()
                    ->columnSpanFull(),

                Infolists\Components\TextEntry::make('responsible_party')
                    ->label('Responsável')
                    ->placeholder('Não identificado'),

                Infolists\Components\TextEntry::make('responsible_area')
                    ->label('Área Responsável')
                    ->placeholder('—'),

                Infolists\Components\TextEntry::make('recurrence')
                    ->label('Periodicidade')
                    ->placeholder('—'),

                Infolists\Components\TextEntry::make('due_rule')
                    ->label('Prazo (conforme texto-fonte)')
                    ->placeholder('Não especificado — verificar trecho de origem'),

                Infolists\Components\TextEntry::make('due_date')
                    ->label('Data de Vencimento')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                Infolists\Components\TextEntry::make('required_evidence')
                    ->label('Evidência Exigida')
                    ->placeholder('Não especificada no texto-fonte')
                    ->columnSpanFull(),
            ]),

            // ── Source audit trail ────────────────────────────────────────────
            Infolists\Components\Section::make('Rastreabilidade — Origem no Termo')->columns(2)->schema([
                Infolists\Components\TextEntry::make('source_clause')
                    ->label('Cláusula de Origem')
                    ->placeholder('Não identificada'),

                Infolists\Components\TextEntry::make('source_page')
                    ->label('Página')
                    ->placeholder('—'),

                Infolists\Components\TextEntry::make('source_excerpt')
                    ->label('Trecho Literal de Origem')
                    ->prose()
                    ->columnSpanFull()
                    ->placeholder('Trecho não disponível'),
            ]),

            // ── AI metadata ───────────────────────────────────────────────────
            Infolists\Components\Section::make('Metadados da Extração por IA')->columns(3)->schema([
                Infolists\Components\TextEntry::make('ai_provider')
                    ->label('Provedor IA')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'gemini' => 'primary',
                        'mock'   => 'gray',
                        default  => 'gray',
                    })
                    ->placeholder('—'),

                Infolists\Components\TextEntry::make('ai_model')
                    ->label('Modelo')
                    ->placeholder('—'),

                Infolists\Components\TextEntry::make('confidence_score')
                    ->label('Confiança')
                    ->formatStateUsing(fn ($state) => $state !== null ? round($state * 100) . '%' : '—')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null    => 'gray',
                        $state >= 0.80     => 'success',
                        $state >= 0.60     => 'warning',
                        default            => 'danger',
                    }),

                Infolists\Components\TextEntry::make('review_notes')
                    ->label('Notas do Modelo de IA')
                    ->placeholder('Nenhuma')
                    ->columnSpanFull(),
            ]),

            // ── Human review ──────────────────────────────────────────────────
            Infolists\Components\Section::make('Revisão Humana')->columns(2)->schema([
                Infolists\Components\TextEntry::make('reviewed_by')
                    ->label('Revisado por')
                    ->placeholder('—'),

                Infolists\Components\TextEntry::make('reviewed_at')
                    ->label('Revisado em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ]),
        ]);
    }
}
