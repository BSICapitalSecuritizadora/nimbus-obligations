<?php

namespace App\Filament\Resources\ObligationResource\Pages;

use App\Filament\Resources\ObligationResource;
use App\Models\Obligation;
use App\Models\ObligationHistory;
use App\Services\NonComplianceRiskService;
use App\Services\ObligationCategoryClassifier;
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
            Actions\Action::make('mark_concluida')
                ->label('Marcar como Concluída')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $old = $this->record->status;
                    $this->record->update(['status' => 'concluida']);
                    ObligationHistory::create([
                        'obligation_id' => $this->record->id,
                        'action'        => 'Obrigação concluída.',
                        'old_value'     => $old,
                        'new_value'     => 'concluida',
                    ]);
                    Notification::make()->title('Obrigação concluída!')->success()->send();
                })
                ->visible(fn () => $this->record->status !== 'concluida'),

            Actions\ActionGroup::make([
                Actions\Action::make('mark_em_analise')
                    ->label('Colocar em Análise')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function () {
                        $old = $this->record->status;
                        $this->record->update(['status' => 'em_analise']);
                        ObligationHistory::create([
                            'obligation_id' => $this->record->id,
                            'action'        => 'Status alterado para Em análise.',
                            'old_value'     => $old,
                            'new_value'     => 'em_analise',
                        ]);
                        Notification::make()->title('Obrigação em análise.')->warning()->send();
                    })
                    ->visible(fn () => $this->record->status !== 'em_analise'),

                Actions\Action::make('mark_waiver')
                    ->label('Waiver / Dispensa')
                    ->icon('heroicon-o-document-check')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function () {
                        $old = $this->record->status;
                        $this->record->update(['status' => 'waiver']);
                        ObligationHistory::create([
                            'obligation_id' => $this->record->id,
                            'action'        => 'Status alterado para Waiver / Dispensa.',
                            'old_value'     => $old,
                            'new_value'     => 'waiver',
                        ]);
                        Notification::make()->title('Obrigação dispensada (waiver).')->success()->send();
                    })
                    ->visible(fn () => $this->record->status !== 'waiver'),

                Actions\Action::make('mark_nao_aplicavel')
                    ->label('Não Aplicável')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function () {
                        $old = $this->record->status;
                        $this->record->update(['status' => 'nao_aplicavel']);
                        ObligationHistory::create([
                            'obligation_id' => $this->record->id,
                            'action'        => 'Status alterado para Não aplicável.',
                            'old_value'     => $old,
                            'new_value'     => 'nao_aplicavel',
                        ]);
                        Notification::make()->title('Obrigação marcada como não aplicável.')->send();
                    })
                    ->visible(fn () => $this->record->status !== 'nao_aplicavel'),

                Actions\Action::make('mark_pendente_evidencia')
                    ->label('Pendente de Evidência')
                    ->icon('heroicon-o-paper-clip')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function () {
                        $old = $this->record->status;
                        $this->record->update(['status' => 'pendente_evidencia']);
                        ObligationHistory::create([
                            'obligation_id' => $this->record->id,
                            'action'        => 'Status alterado para Pendente de evidência.',
                            'old_value'     => $old,
                            'new_value'     => 'pendente_evidencia',
                        ]);
                        Notification::make()->title('Obrigação pendente de evidência.')->info()->send();
                    })
                    ->visible(fn () => $this->record->status !== 'pendente_evidencia'),
            ])->label('Alterar Status'),

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
                Infolists\Components\TextEntry::make('obligation_category')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn ($state) => $state ? ObligationCategoryClassifier::categoryColor($state) : 'gray')
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('non_compliance_risk')
                    ->label('Risco de Descumprimento')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? NonComplianceRiskService::getRiskLabel($state) : '—')
                    ->color(fn ($state) => NonComplianceRiskService::getRiskColor($state ?? ''))
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('status')
                    ->label('Status Operacional')
                    ->formatStateUsing(fn ($state) => Obligation::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'em_dia'             => 'success',
                        'a_vencer'           => 'warning',
                        'vencida'            => 'danger',
                        'concluida'          => 'info',
                        'em_analise'         => 'primary',
                        'waiver'             => 'warning',
                        'nao_aplicavel'      => 'gray',
                        'pendente_evidencia' => 'info',
                        default              => 'gray',
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
                Infolists\Components\TextEntry::make('non_compliance_consequence')
                    ->label('Consequência do Descumprimento')
                    ->placeholder('—')
                    ->columnSpanFull()
                    ->prose(),
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
