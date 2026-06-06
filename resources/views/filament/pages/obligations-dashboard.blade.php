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

    {{-- Obligations table with built-in filters --}}
    <div class="mt-2">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
