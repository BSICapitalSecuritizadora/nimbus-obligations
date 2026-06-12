<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ObligationResource;
use App\Models\Obligation;
use App\Models\Operation;
use App\Services\NonComplianceRiskService;
use App\Services\ObligationCategoryClassifier;
use App\Services\UpcomingObligationsService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ObligationsDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title           = 'Gestão de Obrigações por Emissão';
    protected static ?string $slug            = 'dashboard';
    protected static ?int $navigationSort     = 0;
    protected static string $view             = 'filament.pages.obligations-dashboard';

    // ── filter state ─────────────────────────────────────────────────────────
    public ?string $filterOperation     = null;
    public ?string $filterStatus        = null;
    public ?string $filterPriority      = null;
    public ?string $filterArea          = null;
    public ?string $filterType          = null;

    // ── summary counts ────────────────────────────────────────────────────────
    public function getStats(): array
    {
        $q = Obligation::query();

        return [
            'total'              => (clone $q)->count(),
            'em_dia'             => (clone $q)->where('status', 'em_dia')->count(),
            'a_vencer'           => (clone $q)->where('status', 'a_vencer')->count(),
            'vencida'            => (clone $q)->where('status', 'vencida')->count(),
            'concluida'          => (clone $q)->where('status', 'concluida')->count(),
            'em_analise'         => (clone $q)->where('status', 'em_analise')->count(),
            'waiver'             => (clone $q)->where('status', 'waiver')->count(),
            'nao_aplicavel'      => (clone $q)->where('status', 'nao_aplicavel')->count(),
            'pendente_evidencia' => (clone $q)->where('status', 'pendente_evidencia')->count(),
            'critical'           => (clone $q)->where('priority', 'critical')->count(),
        ];
    }

    public function getOperationOptions(): array
    {
        return Operation::pluck('name', 'id')->toArray();
    }

    public function getUpcomingData(): array
    {
        $svc = app(UpcomingObligationsService::class);

        $toArr = fn ($col) => $col->map(fn ($ob) => [
            'id'                  => $ob->id,
            'title'               => $ob->title,
            'operation_name'      => $ob->operation?->name ?? '—',
            'obligation_category' => $ob->obligation_category,
            'due_date'            => $ob->due_date?->toDateString(),
            'status'              => $ob->status,
            'priority'            => $ob->priority,
            'non_compliance_risk' => $ob->non_compliance_risk,
            'url_view'            => ObligationResource::getUrl('view', ['record' => $ob->id]),
        ])->all();

        return [
            'summary' => $svc->getUpcomingSummary(),
            'overdue' => $toArr($svc->getOverdue(null, 5)),
            'due_7'   => $toArr($svc->getDueIn7Days(null, 5)),
            'due_30'  => $toArr($svc->getDueIn30Days(null, 5)),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->buildQuery())
            ->columns([
                Tables\Columns\TextColumn::make('operation.name')
                    ->label('Operação')
                    ->searchable()
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

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Obligation::statusOptions()[$state] ?? $state)
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

                Tables\Columns\TextColumn::make('non_compliance_risk')
                    ->label('Risco')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? NonComplianceRiskService::getRiskLabel($state) : '—')
                    ->color(fn ($state) => NonComplianceRiskService::getRiskColor($state ?? ''))
                    ->placeholder('—'),

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

                Tables\Filters\SelectFilter::make('responsible_area')
                    ->label('Área Responsável')
                    ->options(fn () => Obligation::distinct()->pluck('responsible_area', 'responsible_area')->filter()->toArray()),

                Tables\Filters\SelectFilter::make('non_compliance_risk')
                    ->label('Risco')
                    ->options(NonComplianceRiskService::getRiskOptions()),

                Tables\Filters\SelectFilter::make('obligation_category')
                    ->label('Categoria')
                    ->options(ObligationCategoryClassifier::categoryOptions()),

                Tables\Filters\SelectFilter::make('obligation_type')
                    ->label('Tipo')
                    ->options(array_combine(Obligation::obligationTypes(), Obligation::obligationTypes())),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Obligation $record) => \App\Filament\Resources\ObligationResource::getUrl('view', ['record' => $record])),

                Tables\Actions\Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (Obligation $record) => \App\Filament\Resources\ObligationResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('due_date', 'asc')
            ->emptyStateHeading('Nenhuma obrigação encontrada')
            ->emptyStateDescription('Cadastre operações, faça upload dos Termos e aprove as sugestões geradas.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    private function buildQuery(): Builder
    {
        return Obligation::query()->with('operation');
    }
}
