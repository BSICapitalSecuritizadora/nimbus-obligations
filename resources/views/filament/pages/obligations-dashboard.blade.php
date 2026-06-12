<x-filament-panels::page>
    {{-- Subtitle --}}
    <p class="text-sm text-gray-500 dark:text-gray-400 -mt-3 mb-6">
        Acompanhamento de obrigações contratuais, prazos, responsáveis e evidências vinculadas a operações de securitização.
        <span class="ml-2 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:ring-amber-700">
            Protótipo — dados reais do banco de dados
        </span>
    </p>

    {{-- Summary cards --}}
    @php $stats = $this->getStats(); @endphp

    <style>
        .dashboard-summary-cards .border-blue-500\/25 { border-color: rgb(59 130 246 / 0.25); }
        .dashboard-summary-cards .border-emerald-500\/25 { border-color: rgb(16 185 129 / 0.25); }
        .dashboard-summary-cards .border-amber-500\/25 { border-color: rgb(245 158 11 / 0.25); }
        .dashboard-summary-cards .border-red-500\/25 { border-color: rgb(239 68 68 / 0.25); }
        .dashboard-summary-cards .border-cyan-500\/25 { border-color: rgb(6 182 212 / 0.25); }
        .dashboard-summary-cards .border-purple-500\/25 { border-color: rgb(168 85 247 / 0.25); }

        .dashboard-summary-cards .bg-blue-500\/\[0\.04\] { background-color: rgb(59 130 246 / 0.04); }
        .dashboard-summary-cards .bg-emerald-500\/\[0\.04\] { background-color: rgb(16 185 129 / 0.04); }
        .dashboard-summary-cards .bg-amber-500\/\[0\.04\] { background-color: rgb(245 158 11 / 0.04); }
        .dashboard-summary-cards .bg-red-500\/\[0\.04\] { background-color: rgb(239 68 68 / 0.04); }
        .dashboard-summary-cards .bg-cyan-500\/\[0\.04\] { background-color: rgb(6 182 212 / 0.04); }
        .dashboard-summary-cards .bg-purple-500\/\[0\.04\] { background-color: rgb(168 85 247 / 0.04); }

        .dashboard-summary-cards .text-blue-400 { color: rgb(96 165 250); }
        .dashboard-summary-cards .text-emerald-400 { color: rgb(52 211 153); }
        .dashboard-summary-cards .text-amber-400 { color: rgb(251 191 36); }
        .dashboard-summary-cards .text-red-400 { color: rgb(248 113 113); }
        .dashboard-summary-cards .text-cyan-400 { color: rgb(34 211 238); }
        .dashboard-summary-cards .text-purple-400 { color: rgb(192 132 252); }

        .dashboard-summary-cards .text-gray-800 { color: rgb(31 41 55); }
        .dashboard-summary-cards .text-gray-600 { color: rgb(75 85 99); }
        .dark .dashboard-summary-cards .dark\:text-gray-100 { color: rgb(243 244 246); }
        .dark .dashboard-summary-cards .dark\:text-gray-300 { color: rgb(209 213 219); }
    </style>

    <div
        class="dashboard-summary-cards grid grid-cols-1 md:grid-cols-[--cols-md] xl:grid-cols-[--cols-xl] gap-4 lg:gap-6 mb-8"
        style="--cols-md: repeat(2, minmax(0, 1fr)); --cols-xl: repeat(3, minmax(0, 1fr));"
    >

        {{-- Total --}}
        <div class="flex flex-col justify-between p-6 min-h-[120px] rounded-xl border border-blue-500/25 bg-blue-500/[0.04] shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wide">Total</h3>
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-500/[0.04] text-blue-400">
                    <x-heroicon-o-rectangle-stack class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4">
                <p class="text-4xl font-extrabold text-blue-400">{{ $stats['total'] }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">obrigações</p>
            </div>
        </div>

        {{-- Em dia --}}
        <div class="flex flex-col justify-between p-6 min-h-[120px] rounded-xl border border-emerald-500/25 bg-emerald-500/[0.04] shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wide">Em dia</h3>
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-500/[0.04] text-emerald-400">
                    <x-heroicon-o-check-circle class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4">
                <p class="text-4xl font-extrabold text-emerald-400">{{ $stats['em_dia'] }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">dentro do prazo</p>
            </div>
        </div>

        {{-- A vencer --}}
        <div class="flex flex-col justify-between p-6 min-h-[120px] rounded-xl border border-amber-500/25 bg-amber-500/[0.04] shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wide">A vencer</h3>
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-amber-500/[0.04] text-amber-400">
                    <x-heroicon-o-clock class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4">
                <p class="text-4xl font-extrabold text-amber-400">{{ $stats['a_vencer'] }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">atenção necessária</p>
            </div>
        </div>

        {{-- Vencidas --}}
        <div class="flex flex-col justify-between p-6 min-h-[120px] rounded-xl border border-red-500/25 bg-red-500/[0.04] shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wide">Vencidas</h3>
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-500/[0.04] text-red-400">
                    <x-heroicon-o-exclamation-circle class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4">
                <p class="text-4xl font-extrabold text-red-400">{{ $stats['vencida'] }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">em atraso</p>
            </div>
        </div>

        {{-- Concluídas --}}
        <div class="flex flex-col justify-between p-6 min-h-[120px] rounded-xl border border-cyan-500/25 bg-cyan-500/[0.04] shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wide">Concluídas</h3>
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-cyan-500/[0.04] text-cyan-400">
                    <x-heroicon-o-check-badge class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4">
                <p class="text-4xl font-extrabold text-cyan-400">{{ $stats['concluida'] }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">finalizadas</p>
            </div>
        </div>

        {{-- Críticas --}}
        <div class="flex flex-col justify-between p-6 min-h-[120px] rounded-xl border border-purple-500/25 bg-purple-500/[0.04] shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wide">Críticas</h3>
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-purple-500/[0.04] text-purple-400">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4">
                <p class="text-4xl font-extrabold text-purple-400">{{ $stats['critical'] }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">prioridade máxima</p>
            </div>
        </div>

    </div>

    {{-- ── Próximos Vencimentos ──────────────────────────────────────────── --}}
    @php
        $upcoming = $this->getUpcomingData();
        $upStatusColor = fn ($s) => match ($s) {
            'vencida' => 'danger', 'a_vencer' => 'warning', 'em_dia' => 'success',
            'concluida' => 'info', 'em_analise' => 'primary', 'waiver' => 'warning',
            'nao_aplicavel' => 'gray', 'pendente_evidencia' => 'info', default => 'gray',
        };
        $upStatusLabel = fn ($s) => match ($s) {
            'vencida' => 'Vencida', 'a_vencer' => 'A vencer', 'em_dia' => 'Em dia',
            'concluida' => 'Concluída', 'em_analise' => 'Em análise', 'waiver' => 'Waiver',
            'nao_aplicavel' => 'N/A', 'pendente_evidencia' => 'Pend. Evidência', default => $s,
        };
        $upPriorityColor = fn ($p) => match ($p) {
            'critical' => 'danger', 'high' => 'warning', 'medium' => 'info', default => 'gray',
        };
        $upPriorityLabel = fn ($p) => match ($p) {
            'critical' => 'Crítica', 'high' => 'Alta', 'medium' => 'Média', default => 'Baixa',
        };
        $upRiskColor = fn ($r) => match ($r) {
            'critico' => 'danger', 'alto' => 'warning', 'medio' => 'info', 'baixo' => 'success', default => 'gray',
        };
        $upRiskLabel = fn ($r) => match ($r) {
            'critico' => 'Crítico', 'alto' => 'Alto', 'medio' => 'Médio', 'baixo' => 'Baixo', default => ($r ?? '—'),
        };
        $sum = $upcoming['summary'];
    @endphp

    <div class="mt-8 mb-2">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Próximos Vencimentos</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Obrigações aprovadas que exigem atenção por data</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-medium">
                @if($sum['overdue_count'] > 0)
                    <span class="flex items-center gap-1 text-red-600 dark:text-red-400">
                        <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                        {{ $sum['overdue_count'] }} vencida{{ $sum['overdue_count'] > 1 ? 's' : '' }}
                    </span>
                @endif
                @if($sum['due_in_7_count'] > 0)
                    <span class="flex items-center gap-1 text-amber-600 dark:text-amber-400">
                        <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
                        {{ $sum['due_in_7_count'] }} em 7 dias
                    </span>
                @endif
                @if($sum['due_in_30_count'] > 0)
                    <span class="flex items-center gap-1 text-blue-600 dark:text-blue-400">
                        <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>
                        {{ $sum['due_in_30_count'] }} em 30 dias
                    </span>
                @endif
            </div>
        </div>

        @if($sum['overdue_count'] + $sum['due_in_7_count'] + $sum['due_in_30_count'] === 0)
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 py-10 flex flex-col items-center justify-center text-center">
                <x-filament::icon icon="heroicon-o-calendar-days" class="h-8 w-8 text-gray-300 dark:text-gray-600 mb-2" />
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nenhuma obrigação com vencimento próximo.</p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                {{-- Vencidas --}}
                <div class="rounded-xl border border-red-200/60 dark:border-red-900/40 bg-red-50/30 dark:bg-red-950/10 p-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-red-600 dark:text-red-400 mb-3 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-red-500 inline-block shrink-0"></span>
                        Vencidas
                        <span class="ml-auto font-bold text-gray-900 dark:text-white text-base leading-none">{{ $sum['overdue_count'] }}</span>
                    </h3>
                    @if(count($upcoming['overdue']) === 0)
                        <p class="text-xs text-gray-400 dark:text-gray-500 italic text-center py-4">Nenhuma obrigação vencida.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($upcoming['overdue'] as $ob)
                                <div class="rounded-lg bg-white dark:bg-gray-900 ring-1 ring-red-100 dark:ring-red-900/30 p-3 hover:ring-red-200 dark:hover:ring-red-700/40 transition-colors">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white leading-snug truncate" title="{{ $ob['title'] }}">{{ $ob['title'] }}</p>
                                        <a href="{{ $ob['url_view'] }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 shrink-0">Ver</a>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                                        {{ $ob['operation_name'] }}
                                        @if($ob['due_date'])
                                            · <span class="font-semibold text-red-600 dark:text-red-400">{{ \Carbon\Carbon::parse($ob['due_date'])->format('d/m/Y') }}</span>
                                        @endif
                                    </p>
                                    <div class="flex flex-wrap gap-1">
                                        <x-filament::badge :color="$upStatusColor($ob['status'])" size="sm">{{ $upStatusLabel($ob['status']) }}</x-filament::badge>
                                        <x-filament::badge :color="$upPriorityColor($ob['priority'])" size="sm">{{ $upPriorityLabel($ob['priority']) }}</x-filament::badge>
                                        @if(!empty($ob['non_compliance_risk']))
                                            <x-filament::badge :color="$upRiskColor($ob['non_compliance_risk'])" size="sm">{{ $upRiskLabel($ob['non_compliance_risk']) }}</x-filament::badge>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Até 7 dias --}}
                <div class="rounded-xl border border-amber-200/60 dark:border-amber-900/40 bg-amber-50/30 dark:bg-amber-950/10 p-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 mb-3 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500 inline-block shrink-0"></span>
                        Até 7 Dias
                        <span class="ml-auto font-bold text-gray-900 dark:text-white text-base leading-none">{{ $sum['due_in_7_count'] }}</span>
                    </h3>
                    @if(count($upcoming['due_7']) === 0)
                        <p class="text-xs text-gray-400 dark:text-gray-500 italic text-center py-4">Nenhuma obrigação vence nos próximos 7 dias.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($upcoming['due_7'] as $ob)
                                <div class="rounded-lg bg-white dark:bg-gray-900 ring-1 ring-amber-100 dark:ring-amber-900/30 p-3 hover:ring-amber-200 dark:hover:ring-amber-700/40 transition-colors">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white leading-snug truncate" title="{{ $ob['title'] }}">{{ $ob['title'] }}</p>
                                        <a href="{{ $ob['url_view'] }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 shrink-0">Ver</a>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                                        {{ $ob['operation_name'] }}
                                        @if($ob['due_date'])
                                            · <span class="font-semibold text-amber-600 dark:text-amber-400">{{ \Carbon\Carbon::parse($ob['due_date'])->format('d/m/Y') }}</span>
                                        @endif
                                    </p>
                                    <div class="flex flex-wrap gap-1">
                                        <x-filament::badge :color="$upStatusColor($ob['status'])" size="sm">{{ $upStatusLabel($ob['status']) }}</x-filament::badge>
                                        <x-filament::badge :color="$upPriorityColor($ob['priority'])" size="sm">{{ $upPriorityLabel($ob['priority']) }}</x-filament::badge>
                                        @if(!empty($ob['non_compliance_risk']))
                                            <x-filament::badge :color="$upRiskColor($ob['non_compliance_risk'])" size="sm">{{ $upRiskLabel($ob['non_compliance_risk']) }}</x-filament::badge>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Até 30 dias --}}
                <div class="rounded-xl border border-blue-200/60 dark:border-blue-900/40 bg-blue-50/30 dark:bg-blue-950/10 p-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-3 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500 inline-block shrink-0"></span>
                        Até 30 Dias
                        <span class="ml-auto font-bold text-gray-900 dark:text-white text-base leading-none">{{ $sum['due_in_30_count'] }}</span>
                    </h3>
                    @if(count($upcoming['due_30']) === 0)
                        <p class="text-xs text-gray-400 dark:text-gray-500 italic text-center py-4">Nenhuma obrigação vence nos próximos 30 dias.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($upcoming['due_30'] as $ob)
                                <div class="rounded-lg bg-white dark:bg-gray-900 ring-1 ring-blue-100 dark:ring-blue-900/30 p-3 hover:ring-blue-200 dark:hover:ring-blue-700/40 transition-colors">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white leading-snug truncate" title="{{ $ob['title'] }}">{{ $ob['title'] }}</p>
                                        <a href="{{ $ob['url_view'] }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 shrink-0">Ver</a>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                                        {{ $ob['operation_name'] }}
                                        @if($ob['due_date'])
                                            · <span class="font-semibold text-blue-600 dark:text-blue-400">{{ \Carbon\Carbon::parse($ob['due_date'])->format('d/m/Y') }}</span>
                                        @endif
                                    </p>
                                    <div class="flex flex-wrap gap-1">
                                        <x-filament::badge :color="$upStatusColor($ob['status'])" size="sm">{{ $upStatusLabel($ob['status']) }}</x-filament::badge>
                                        <x-filament::badge :color="$upPriorityColor($ob['priority'])" size="sm">{{ $upPriorityLabel($ob['priority']) }}</x-filament::badge>
                                        @if(!empty($ob['non_compliance_risk']))
                                            <x-filament::badge :color="$upRiskColor($ob['non_compliance_risk'])" size="sm">{{ $upRiskLabel($ob['non_compliance_risk']) }}</x-filament::badge>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>
        @endif
    </div>

    {{-- Obligations table with built-in filters --}}
    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
