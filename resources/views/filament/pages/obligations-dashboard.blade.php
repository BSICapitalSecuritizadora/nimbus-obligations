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

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">

        {{-- Total --}}
        <div class="relative overflow-hidden rounded-xl bg-blue-600 p-5 shadow-md ring-1 ring-blue-500/50">
            <div class="absolute -right-3 -top-3 h-16 w-16 rounded-full bg-blue-500/30"></div>
            <p class="relative text-xs font-semibold uppercase tracking-widest text-blue-100">Total</p>
            <p class="relative mt-2 text-4xl font-extrabold text-white">{{ $stats['total'] }}</p>
            <p class="relative mt-1 text-xs text-blue-200">obrigações</p>
        </div>

        {{-- Em dia --}}
        <div class="relative overflow-hidden rounded-xl bg-emerald-600 p-5 shadow-md ring-1 ring-emerald-500/50">
            <div class="absolute -right-3 -top-3 h-16 w-16 rounded-full bg-emerald-500/30"></div>
            <p class="relative text-xs font-semibold uppercase tracking-widest text-emerald-100">Em dia</p>
            <p class="relative mt-2 text-4xl font-extrabold text-white">{{ $stats['on_track'] }}</p>
            <p class="relative mt-1 text-xs text-emerald-200">dentro do prazo</p>
        </div>

        {{-- A vencer --}}
        <div class="relative overflow-hidden rounded-xl bg-amber-500 p-5 shadow-md ring-1 ring-amber-400/50">
            <div class="absolute -right-3 -top-3 h-16 w-16 rounded-full bg-amber-400/30"></div>
            <p class="relative text-xs font-semibold uppercase tracking-widest text-amber-100">A vencer</p>
            <p class="relative mt-2 text-4xl font-extrabold text-white">{{ $stats['due_soon'] }}</p>
            <p class="relative mt-1 text-xs text-amber-100">atenção necessária</p>
        </div>

        {{-- Vencidas --}}
        <div class="relative overflow-hidden rounded-xl bg-red-600 p-5 shadow-md ring-1 ring-red-500/50">
            <div class="absolute -right-3 -top-3 h-16 w-16 rounded-full bg-red-500/30"></div>
            <p class="relative text-xs font-semibold uppercase tracking-widest text-red-100">Vencidas</p>
            <p class="relative mt-2 text-4xl font-extrabold text-white">{{ $stats['overdue'] }}</p>
            <p class="relative mt-1 text-xs text-red-200">em atraso</p>
        </div>

        {{-- Concluídas --}}
        <div class="relative overflow-hidden rounded-xl bg-cyan-600 p-5 shadow-md ring-1 ring-cyan-500/50">
            <div class="absolute -right-3 -top-3 h-16 w-16 rounded-full bg-cyan-500/30"></div>
            <p class="relative text-xs font-semibold uppercase tracking-widest text-cyan-100">Concluídas</p>
            <p class="relative mt-2 text-4xl font-extrabold text-white">{{ $stats['completed'] }}</p>
            <p class="relative mt-1 text-xs text-cyan-200">finalizadas</p>
        </div>

        {{-- Críticas --}}
        <div class="relative overflow-hidden rounded-xl bg-purple-700 p-5 shadow-md ring-1 ring-purple-500/50">
            <div class="absolute -right-3 -top-3 h-16 w-16 rounded-full bg-purple-500/30"></div>
            <p class="relative text-xs font-semibold uppercase tracking-widest text-purple-200">Críticas</p>
            <p class="relative mt-2 text-4xl font-extrabold text-white">{{ $stats['critical'] }}</p>
            <p class="relative mt-1 text-xs text-purple-300">prioridade máxima</p>
        </div>

    </div>

    {{-- Obligations table with built-in filters --}}
    <div class="mt-2">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
