<x-filament-panels::page>

@php
$priorityColor = fn ($p) => match ($p) {
    'critical' => 'danger',
    'high'     => 'warning',
    'medium'   => 'info',
    default    => 'gray',
};
$priorityLabel = fn ($p) => match ($p) {
    'critical' => 'Crítica',
    'high'     => 'Alta',
    'medium'   => 'Média',
    default    => 'Baixa',
};
$obStatusColor = fn ($s) => match ($s) {
    'overdue'      => 'danger',
    'due_soon'     => 'warning',
    'on_track'     => 'success',
    'completed'    => 'gray',
    'under_review' => 'info',
    default        => 'gray',
};
$obStatusLabel = fn ($s) => match ($s) {
    'overdue'      => 'Vencida',
    'due_soon'     => 'A vencer',
    'on_track'     => 'Em dia',
    'completed'    => 'Concluída',
    'under_review' => 'Em análise',
    default        => $s,
};
$procStatusColor = fn ($s) => match ($s) {
    'processed'  => 'success',
    'processing' => 'warning',
    'failed'     => 'danger',
    default      => 'gray',
};
$procStatusLabel = fn ($s) => match ($s) {
    'processed'  => 'Processado',
    'processing' => 'Processando',
    'pending'    => 'Pendente',
    'failed'     => 'Falhou',
    default      => $s,
};
@endphp

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION NAVIGATION                                                        --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}

<div class="flex items-center gap-6 overflow-x-auto border-b border-gray-200 dark:border-gray-800 pb-2 text-sm font-medium text-gray-500 dark:text-gray-400 -mt-2">
    <a href="#resumo" class="hover:text-primary-600 dark:hover:text-primary-400 whitespace-nowrap">Resumo Executivo</a>
    <a href="#obrigacoes" class="hover:text-primary-600 dark:hover:text-primary-400 whitespace-nowrap">Obrigações Aprovadas</a>
    <a href="#sugestoes" class="hover:text-primary-600 dark:hover:text-primary-400 whitespace-nowrap">Sugestões (IA)</a>
    <a href="#documentos" class="hover:text-primary-600 dark:hover:text-primary-400 whitespace-nowrap">Termos & Documentos</a>
    <a href="#distribuicao" class="hover:text-primary-600 dark:hover:text-primary-400 whitespace-nowrap">Distribuição</a>
    <a href="#roadmap" class="hover:text-primary-600 dark:hover:text-primary-400 whitespace-nowrap">Roadmap</a>
</div>

<div id="resumo" class="scroll-mt-8"></div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- ABOVE-THE-FOLD: OPERATION IDENTITY & EXECUTIVE STATUS                     --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Left: Operation Identity Card --}}
    <div class="xl:col-span-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex flex-col justify-between">
        <div>
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $record->name }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Série: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $record->series ?: '—' }}</span> • IF: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $record->if_code ?: '—' }}</span></p>
                </div>
                <div class="flex items-center gap-2">
                    <x-filament::badge color="primary">
                        {{ \App\Models\Operation::typeOptions()[$record->type] ?? $record->type }}
                    </x-filament::badge>
                    <x-filament::badge :color="match($record->status) { 'active' => 'success', 'draft' => 'gray', 'closed' => 'danger', default => 'gray' }">
                        {{ \App\Models\Operation::statusOptions()[$record->status] ?? $record->status }}
                    </x-filament::badge>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-5 gap-x-6 mt-6 pt-5 border-t border-gray-100 dark:border-gray-800">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-0.5">Emissora / Securitizadora</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $record->issuer }}">{{ $record->issuer ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-0.5">Devedor / Cedente</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $record->debtor ?: $record->assignor }}">{{ $record->debtor ?: ($record->assignor ?: '—') }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-0.5">Agente Fiduciário</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $record->fiduciary_agent }}">{{ $record->fiduciary_agent ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-0.5">Datas</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $record->issue_date ? $record->issue_date->format('d/m/Y') : '—' }}
                        <span class="text-gray-400 mx-1">→</span>
                        {{ $record->maturity_date ? $record->maturity_date->format('d/m/Y') : '—' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Executive Status Card --}}
    @php
    $healthBg = match($health['status']) {
        'critical'  => 'bg-rose-50/50 ring-rose-200 dark:bg-rose-950/20 dark:ring-rose-900/40',
        'attention' => 'bg-amber-50/50 ring-amber-200 dark:bg-amber-950/20 dark:ring-amber-900/40',
        default     => 'bg-emerald-50/50 ring-emerald-200 dark:bg-emerald-950/20 dark:ring-emerald-900/40',
    };
    $healthTitle = match($health['status']) {
        'critical'  => 'Crítico',
        'attention' => 'Atenção',
        default     => 'Saudável',
    };
    $healthColor = match($health['status']) {
        'critical'  => 'danger',
        'attention' => 'warning',
        default     => 'success',
    };
    $healthTextColor = match($health['status']) {
        'critical'  => 'text-rose-700 dark:text-rose-400',
        'attention' => 'text-amber-700 dark:text-amber-400',
        default     => 'text-emerald-700 dark:text-emerald-400',
    };
    $healthSupportText = match($health['status']) {
        'critical'  => 'A operação possui pendências que podem impactar o acompanhamento contratual.',
        'attention' => 'Existem itens próximos ao vencimento ou sugestões pendentes de revisão.',
        default     => 'Todos os indicadores acompanhados estão dentro da normalidade esperada.',
    };
    @endphp

    <div class="rounded-xl p-6 shadow-sm ring-1 flex flex-col justify-between {{ $healthBg }}">
        <div>
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status da Operação</p>
                <x-filament::badge :color="$healthColor" class="uppercase font-bold tracking-widest text-[10px]">
                    {{ $healthTitle }}
                </x-filament::badge>
            </div>
            
            <p class="text-sm font-semibold {{ $healthTextColor }} leading-snug">
                {{ $health['main_point'] }}
            </p>
            <p class="text-[11px] text-gray-600 dark:text-gray-500 mt-1.5 leading-relaxed">
                {{ $healthSupportText }}
            </p>

            <div class="mt-4 pt-4 border-t border-black/5 dark:border-white/5">
                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Ação Recomendada</p>
                <p class="text-xs font-medium text-gray-900 dark:text-white leading-relaxed">
                    {{ $health['recommended_action'] }}
                </p>
            </div>
        </div>

        <div class="mt-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5 border-y border-black/5 dark:border-white/5 py-4">
                <div class="flex flex-col">
                    <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-500 dark:text-gray-400">Críticas</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $health['critical_count'] }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-500 dark:text-gray-400">Sugestões</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $health['pending_suggestions'] }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-500 dark:text-gray-400">Última IA</span>
                    <span class="text-xs font-semibold text-gray-900 dark:text-white mt-0.5">{{ $health['last_ai_processing'] ?? '—' }}</span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-end gap-3">
                <x-filament::button tag="a" href="#obrigacoes" color="gray" size="sm" outlined class="w-full sm:w-auto justify-center">
                    Ver obrigações críticas
                </x-filament::button>
                
                @if($health['pending_suggestions'] > 0)
                    <x-filament::button tag="a" href="#sugestoes" :color="in_array($health['status'], ['attention', 'critical']) ? 'warning' : 'primary'" size="sm" class="w-full sm:w-auto justify-center">
                        Revisar sugestões
                    </x-filament::button>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- EXECUTIVE SUMMARY METRICS GRID                                            --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">

    {{-- Aprovadas (Blue) --}}
    <div class="rounded-xl bg-blue-50/30 dark:bg-blue-950/20 px-4 py-3 ring-1 ring-blue-100 dark:ring-blue-900/30 flex items-center justify-between transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/30 h-20">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-0.5">Aprovadas</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['approved_count'] }}</p>
        </div>
        <div class="rounded-full bg-blue-100 dark:bg-blue-900/50 p-1.5 shrink-0">
            <x-filament::icon icon="heroicon-o-check-badge" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
        </div>
    </div>

    {{-- Sugestões (Amber) --}}
    <div class="rounded-xl bg-amber-50/30 dark:bg-amber-950/20 px-4 py-3 ring-1 ring-amber-100 dark:ring-amber-900/30 flex items-center justify-between transition-colors hover:bg-amber-50 dark:hover:bg-amber-900/30 h-20">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 mb-0.5">Sugestões Pendentes</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['suggested_count'] }}</p>
        </div>
        <div class="rounded-full bg-amber-100 dark:bg-amber-900/50 p-1.5 shrink-0">
            <x-filament::icon icon="heroicon-o-inbox-stack" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
        </div>
    </div>

    {{-- Críticas (Rose) --}}
    <div class="rounded-xl bg-rose-50/30 dark:bg-rose-950/20 px-4 py-3 ring-1 ring-rose-100 dark:ring-rose-900/30 flex items-center justify-between transition-colors hover:bg-rose-50 dark:hover:bg-rose-900/30 h-20">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 mb-0.5">Críticas (Ativas)</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['critical_count'] }}</p>
        </div>
        <div class="rounded-full bg-rose-100 dark:bg-rose-900/50 p-1.5 shrink-0">
            <x-filament::icon icon="heroicon-o-shield-exclamation" class="h-5 w-5 text-rose-600 dark:text-rose-400" />
        </div>
    </div>

    {{-- Vencidas (Red) --}}
    <div class="rounded-xl bg-red-50/30 dark:bg-red-950/20 px-4 py-3 ring-1 ring-red-100 dark:ring-red-900/30 flex items-center justify-between transition-colors hover:bg-red-50 dark:hover:bg-red-900/30 h-20">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-red-600 dark:text-red-400 mb-0.5">Vencidas</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['overdue_count'] }}</p>
        </div>
        <div class="rounded-full bg-red-100 dark:bg-red-900/50 p-1.5 shrink-0">
            <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 text-red-600 dark:text-red-400" />
        </div>
    </div>

    {{-- A Vencer (Amber) --}}
    <div class="rounded-xl bg-amber-50/30 dark:bg-amber-950/20 px-4 py-3 ring-1 ring-amber-100 dark:ring-amber-900/30 flex items-center justify-between transition-colors hover:bg-amber-50 dark:hover:bg-amber-900/30 h-20">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 mb-0.5">A Vencer</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['due_soon_count'] }}</p>
        </div>
        <div class="rounded-full bg-amber-100 dark:bg-amber-900/50 p-1.5 shrink-0">
            <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
        </div>
    </div>

    {{-- Concluídas (Green) --}}
    <div class="rounded-xl bg-emerald-50/30 dark:bg-emerald-950/20 px-4 py-3 ring-1 ring-emerald-100 dark:ring-emerald-900/30 flex items-center justify-between transition-colors hover:bg-emerald-50 dark:hover:bg-emerald-900/30 h-20">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-0.5">Concluídas</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['completed_count'] }}</p>
        </div>
        <div class="rounded-full bg-emerald-100 dark:bg-emerald-900/50 p-1.5 shrink-0">
            <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
        </div>
    </div>

    {{-- Termos (Cyan) --}}
    <div class="rounded-xl bg-cyan-50/30 dark:bg-cyan-950/20 px-4 py-3 ring-1 ring-cyan-100 dark:ring-cyan-900/30 flex items-center justify-between transition-colors hover:bg-cyan-50 dark:hover:bg-cyan-900/30 h-20">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-cyan-600 dark:text-cyan-400 mb-0.5">Termos Processados</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ $stats['term_documents_count'] }}</p>
        </div>
        <div class="rounded-full bg-cyan-100 dark:bg-cyan-900/50 p-1.5 shrink-0">
            <x-filament::icon icon="heroicon-o-document-duplicate" class="h-5 w-5 text-cyan-600 dark:text-cyan-400" />
        </div>
    </div>

    {{-- Última IA (Cyan) --}}
    <div class="rounded-xl bg-cyan-50/30 dark:bg-cyan-950/20 px-4 py-3 ring-1 ring-cyan-100 dark:ring-cyan-900/30 flex items-center justify-between transition-colors hover:bg-cyan-50 dark:hover:bg-cyan-900/30 h-20">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-cyan-600 dark:text-cyan-400 mb-0.5">Última Execução IA</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white leading-none">{{ $stats['last_ai_processing'] }}</p>
        </div>
        <div class="rounded-full bg-cyan-100 dark:bg-cyan-900/50 p-1.5 shrink-0">
            <x-filament::icon icon="heroicon-o-sparkles" class="h-5 w-5 text-cyan-600 dark:text-cyan-400" />
        </div>
    </div>

</div>

<div id="obrigacoes" class="scroll-mt-8 mt-8"></div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- APPROVED OBLIGATIONS (MOST URGENT)                                        --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}

<x-filament::section heading="Obrigações Aprovadas (Mais Urgentes)">

    @if(count($approvedObs) === 0)
        <div class="py-12 flex flex-col items-center justify-center text-center">
            <div class="rounded-full bg-gray-50 p-4 dark:bg-gray-800/50 mb-3">
                <x-filament::icon icon="heroicon-o-document-check" class="h-6 w-6 text-gray-400 dark:text-gray-500" />
            </div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Nenhuma obrigação aprovada ainda.</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-xs">Aguardando revisão de sugestões ou criação manual de obrigações.</p>
        </div>
    @else
        <div class="overflow-x-auto w-full">
            <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-800">
                <thead>
                    <tr>
                        <th class="py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] w-1/3">Título</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Categoria</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Área</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Vencimento</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Status</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Prioridade</th>
                        <th class="py-3 text-right font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @foreach($approvedObs as $ob)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/25 transition-colors">
                            <td class="py-3 font-medium text-gray-900 dark:text-white max-w-0 w-1/3">
                                <span class="truncate block" title="{{ $ob['title'] }}">{{ $ob['title'] }}</span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if($ob['obligation_category'])
                                    <x-filament::badge :color="\App\Services\ObligationCategoryClassifier::categoryColor($ob['obligation_category'])">
                                        {{ $ob['obligation_category'] }}
                                    </x-filament::badge>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ $ob['responsible_area'] ?: '—' }}
                            </td>
                            <td class="px-3 py-3 font-medium whitespace-nowrap {{ $ob['status'] === 'overdue' ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $ob['due_date'] ? \Carbon\Carbon::parse($ob['due_date'])->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <x-filament::badge :color="$obStatusColor($ob['status'])">
                                    {{ $obStatusLabel($ob['status']) }}
                                </x-filament::badge>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <x-filament::badge :color="$priorityColor($ob['priority'])">
                                    {{ $priorityLabel($ob['priority']) }}
                                </x-filament::badge>
                            </td>
                            <td class="py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ $ob['url_view'] }}" class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">Ver</a>
                                    <a href="{{ $ob['url_edit'] }}" class="font-medium text-gray-600 hover:text-gray-700 dark:text-gray-400">Editar</a>
                                    @if($ob['status'] !== 'completed')
                                        <button wire:click="markObligationCompleted({{ $ob['id'] }})" wire:confirm="Marcar como concluída?" class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Concluir</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
            <x-filament::button tag="a" href="{{ $obligationsIndexUrl }}" color="gray" size="sm" icon="heroicon-o-arrow-right" icon-position="after">
                Ver todas as {{ $stats['approved_count'] }} obrigações
            </x-filament::button>
        </div>
    @endif

</x-filament::section>

<div id="sugestoes" class="scroll-mt-8 mt-8"></div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SUGGESTED OBLIGATIONS AWAITING REVIEW                                     --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}

<x-filament::section heading="Sugestões Pendentes (IA)">

    @if(count($suggestedObs) === 0)
        <div class="py-12 flex flex-col items-center justify-center text-center">
            <div class="rounded-full bg-gray-50 p-4 dark:bg-gray-800/50 mb-3">
                <x-filament::icon icon="heroicon-o-check-badge" class="h-6 w-6 text-gray-400 dark:text-gray-500" />
            </div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Nenhuma sugestão pendente para revisão.</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-xs">A inteligência artificial não extraiu novas sugestões ou todas já foram revisadas.</p>
        </div>
    @else
        <div class="overflow-x-auto w-full">
            <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-800">
                <thead>
                    <tr>
                        <th class="py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] w-1/3">Título</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Categoria</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Prioridade</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Confiança</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] w-1/4">Cláusula Origem</th>
                        <th class="py-3 text-right font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @foreach($suggestedObs as $ob)
                        @php
                            $conf = $ob['confidence_score'];
                            $confColor = match(true) {
                                $conf === null  => 'text-gray-400',
                                $conf >= 0.80   => 'text-emerald-600 dark:text-emerald-400',
                                $conf >= 0.60   => 'text-amber-600 dark:text-amber-400',
                                default         => 'text-red-600 dark:text-red-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/25 transition-colors">
                            <td class="py-3 font-medium text-gray-900 dark:text-white max-w-0 w-1/3">
                                <p class="truncate" title="{{ $ob['title'] }}">{{ $ob['title'] }}</p>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if(!empty($ob['obligation_category']))
                                    <x-filament::badge :color="\App\Services\ObligationCategoryClassifier::categoryColor($ob['obligation_category'])">
                                        {{ $ob['obligation_category'] }}
                                    </x-filament::badge>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <x-filament::badge :color="$priorityColor($ob['priority'])">
                                    {{ $priorityLabel($ob['priority']) }}
                                </x-filament::badge>
                            </td>
                            <td class="px-3 py-3 font-semibold whitespace-nowrap {{ $confColor }}">
                                {{ $conf !== null ? round($conf * 100) . '%' : '—' }}
                            </td>
                            <td class="px-3 py-3 text-gray-500 dark:text-gray-400 max-w-0 w-1/4">
                                <p class="truncate" title="{{ $ob['source_clause'] }}">{{ $ob['source_clause'] ?: '—' }}</p>
                            </td>
                            <td class="py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ $ob['url_view'] }}" class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">Ver</a>
                                    <button wire:click="approveSuggestion({{ $ob['id'] }})" wire:confirm="Aprovar e criar a obrigação?" class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Aprovar</button>
                                    <button wire:click="rejectSuggestion({{ $ob['id'] }})" wire:confirm="Rejeitar esta sugestão?" class="font-medium text-red-600 hover:text-red-700 dark:text-red-400">Rejeitar</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
            <x-filament::button tag="a" href="{{ $extractedObligationsIndexUrl }}" color="gray" size="sm" icon="heroicon-o-arrow-right" icon-position="after">
                Revisar todas as {{ $stats['suggested_count'] }} sugestões
            </x-filament::button>
        </div>
    @endif

</x-filament::section>

<div id="documentos" class="scroll-mt-8 mt-8"></div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TERMOS DE SECURITIZAÇÃO                                                   --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}

<x-filament::section heading="Termos de Securitização">

    @if(count($termDocuments) === 0)
        <div class="py-12 flex flex-col items-center justify-center text-center">
            <div class="rounded-full bg-gray-50 p-4 dark:bg-gray-800/50 mb-3">
                <x-filament::icon icon="heroicon-o-document-text" class="h-6 w-6 text-gray-400 dark:text-gray-500" />
            </div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Nenhum Termo de Securitização enviado para esta operação.</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Faça o upload do documento para iniciar a extração de inteligência artificial.</p>
        </div>
    @else
        <div class="overflow-x-auto w-full">
            <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-800">
                <thead>
                    <tr>
                        <th class="py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] w-1/3">Arquivo</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Processamento</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">IA Status</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Modelo</th>
                        <th class="px-3 py-3 text-center font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Sugestões</th>
                        <th class="px-3 py-3 text-left font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Data Proc.</th>
                        <th class="py-3 text-right font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 text-[11px] whitespace-nowrap">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @foreach($termDocuments as $doc)
                        @php
                            $meta        = $doc['extraction_metadata'] ?? [];
                            $genStatus   = $meta['generation_status'] ?? null;
                            $suggestions = $meta['obligations_created'] ?? ($meta['suggestions_generated'] ?? null);
                            $aiStatusColor = match($genStatus) {
                                'completed' => 'success',
                                'processing','queued' => 'warning',
                                'failed'    => 'danger',
                                default     => 'gray',
                            };
                            $aiStatusLabel = match($genStatus) {
                                'completed' => 'Concluído',
                                'processing'=> 'Processando',
                                'queued'    => 'Na fila',
                                'failed'    => 'Falhou',
                                default     => '—',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/25 transition-colors">
                            <td class="py-3 font-medium text-gray-900 dark:text-white max-w-0 w-1/3">
                                <div class="flex items-center gap-2">
                                    <x-filament::icon icon="heroicon-o-document" class="h-4 w-4 text-gray-400 shrink-0" />
                                    <p class="truncate" title="{{ $doc['original_filename'] }}">{{ $doc['original_filename'] }}</p>
                                </div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <x-filament::badge :color="$procStatusColor($doc['processing_status'])">
                                    {{ $procStatusLabel($doc['processing_status']) }}
                                </x-filament::badge>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if($genStatus)
                                    <x-filament::badge :color="$aiStatusColor">{{ $aiStatusLabel }}</x-filament::badge>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                @if($doc['extraction_provider'])
                                    <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst($doc['extraction_provider']) }}</span>
                                    @if($doc['extraction_model'])
                                        <span class="text-gray-500 block text-[10px] truncate max-w-[150px]">{{ $doc['extraction_model'] }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-3 text-gray-700 dark:text-gray-300 text-center font-medium whitespace-nowrap">
                                {{ $suggestions ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $doc['processed_at'] ? \Carbon\Carbon::parse($doc['processed_at'])->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td class="py-3 text-right whitespace-nowrap">
                                <a href="{{ $doc['url_view'] }}" class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">Ver documento</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</x-filament::section>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
    <div id="distribuicao" class="scroll-mt-8">
        {{-- ══════════════════════════════════════════════════════════════════════════ --}}
        {{-- CATEGORY BREAKDOWN                                                        --}}
        {{-- ══════════════════════════════════════════════════════════════════════════ --}}
        <x-filament::section heading="Distribuição por Categoria">
            @if(count($categoryBreakdown) === 0)
                <div class="py-12 flex flex-col items-center justify-center text-center">
                    <div class="rounded-full bg-gray-50 p-4 dark:bg-gray-800/50 mb-3">
                        <x-filament::icon icon="heroicon-o-chart-bar" class="h-6 w-6 text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Nenhuma obrigação aprovada classificada por categoria.</h3>
                </div>
            @else
                @php 
                    $breakdown = $categoryBreakdown;
                    // Ensure the list is sorted by count descending, then alphabetically (including 'Outros')
                    usort($breakdown, function($a, $b) {
                        if ($a['count'] === $b['count']) {
                            return strcmp($a['type'], $b['type']);
                        }
                        return $b['count'] <=> $a['count'];
                    });
                    
                    $totalObs = array_sum(array_column($breakdown, 'count')); 
                    $numTypes = count($breakdown);
                    $largestType = $breakdown[0];
                    $largestPct = $totalObs > 0 ? round(($largestType['count'] / $totalObs) * 100) : 0;
                @endphp
                
                <div class="mb-5 pb-5 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            <span class="font-bold text-gray-900 dark:text-white">{{ $totalObs }}</span> obrigações classificadas em <span class="font-bold text-gray-900 dark:text-white">{{ $numTypes }}</span> categorias
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary-500"></span>
                            Maior concentração: <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $largestType['type'] }}</span> — {{ $largestPct }}%
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-4">
                    @foreach($breakdown as $index => $cat)
                        @php
                            $pct = $totalObs > 0 ? round(($cat['count'] / $totalObs) * 100) : 0;
                            $isLargest = ($index === 0);
                        @endphp
                        <div class="group">
                            <div class="flex items-center justify-between text-sm mb-1.5">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="{{ $isLargest ? 'font-bold text-gray-900 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-300' }} truncate group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors" title="{{ $cat['type'] }}">
                                        {{ $cat['type'] }}
                                    </span>
                                    @if($isLargest)
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-primary-600 bg-primary-50 dark:bg-primary-900/30 dark:text-primary-400 px-1.5 py-0.5 rounded shrink-0">Maior</span>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $cat['count'] }}</span> obrigações &middot; {{ $pct }}%
                                </span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full rounded-full {{ $isLargest ? 'bg-primary-500 dark:bg-primary-500' : 'bg-primary-300 dark:bg-primary-700' }} transition-all duration-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>

    <div id="roadmap" class="scroll-mt-8">
        {{-- ══════════════════════════════════════════════════════════════════════════ --}}
        {{-- ROADMAP / PRÓXIMAS EVOLUÇÕES                                              --}}
        {{-- ══════════════════════════════════════════════════════════════════════════ --}}
        <x-filament::section heading="Roadmap do Produto">
            <div class="grid grid-cols-1 gap-4">
                @foreach([
                    ['icon' => 'heroicon-o-paper-clip', 'title' => 'Upload de Evidências', 'desc' => 'Anexar comprovantes por obrigação', 'status' => 'Planejado'],
                    ['icon' => 'heroicon-o-bell', 'title' => 'Alertas Automáticos', 'desc' => 'Notificações por e-mail de vencimentos', 'status' => 'Planejado'],
                    ['icon' => 'heroicon-o-calendar', 'title' => 'Sincronização de Calendário', 'desc' => 'Integração com Outlook e Google', 'status' => 'Futuro'],
                    ['icon' => 'heroicon-o-chart-bar', 'title' => 'Monitoramento Financeiro', 'desc' => 'Acompanhamento de garantias e fundos', 'status' => 'Futuro'],
                    ['icon' => 'heroicon-o-arrows-right-left', 'title' => 'Integração de Sistemas', 'desc' => 'Sincronização com ERP e sistema core', 'status' => 'Futuro'],
                ] as $item)
                    <div class="flex items-start gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                        <div class="rounded-lg bg-white dark:bg-gray-800 p-2 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 shrink-0">
                            <x-filament::icon :icon="$item['icon']" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 mb-0.5">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $item['title'] }}</h4>
                                <x-filament::badge :color="$item['status'] === 'Planejado' ? 'info' : 'gray'" size="sm">
                                    {{ $item['status'] }}
                                </x-filament::badge>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $item['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</div>

</x-filament-panels::page>
