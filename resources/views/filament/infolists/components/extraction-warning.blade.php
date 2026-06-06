@php
    $record   = $getRecord();
    $warnings = [];

    if ($record->confidence_score !== null && $record->confidence_score < 0.60) {
        $warnings[] = 'Baixa confiança (' . round($record->confidence_score * 100) . '%) — o modelo estava inseguro ao extrair esta obrigação';
    }
    if (empty($record->source_clause)) {
        $warnings[] = 'Cláusula de origem não identificada — não foi possível rastrear a cláusula exata';
    }
    if (empty($record->due_rule)) {
        $warnings[] = 'Prazo não identificado no texto-fonte — verificar se há cláusula complementar';
    }
    if (empty($record->responsible_party)) {
        $warnings[] = 'Responsável não explícito no trecho de origem';
    }
    if (!empty($record->review_notes)) {
        $warnings[] = 'O modelo de IA adicionou notas de revisão — leia antes de aprovar';
    }
@endphp

@if(count($warnings) > 0)
    <div class="fi-section rounded-xl border border-amber-300 bg-amber-50 px-6 py-4 dark:border-amber-600/50 dark:bg-amber-900/20">
        <div class="flex gap-3">
            <div class="mt-0.5 flex-shrink-0 text-amber-500">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                    Revisão recomendada
                </h3>
                <p class="mt-0.5 text-sm text-amber-700 dark:text-amber-300">
                    A obrigação foi extraída com baixa confiança ou possui campos inferidos/ausentes. Verifique o trecho de origem antes de aprovar.
                </p>
                <ul class="mt-2 space-y-1">
                    @foreach($warnings as $warning)
                        <li class="flex items-start gap-1.5 text-sm text-amber-700 dark:text-amber-300">
                            <span class="mt-1 flex-shrink-0 h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                            {{ $warning }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
