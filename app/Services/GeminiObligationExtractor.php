<?php

namespace App\Services;

use App\Contracts\ObligationExtractorInterface;
use App\Models\TermDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Extracts contractual obligations from a TermDocument using Google Gemini.
 *
 * Flow:
 *  1. Validate API key is configured.
 *  2. Build text chunks from TermDocumentChunk records (or raw extracted_text).
 *  3. Optionally redact sensitive data from each chunk.
 *  4. Send each chunk group to Gemini with a strict grounding-based extraction prompt.
 *  5. Parse, validate and source-ground the JSON responses.
 *  6. Deduplicate across chunks, preferring higher-quality candidates.
 *  7. Return the final obligation proposals array.
 *
 * Design invariants:
 *  - Never throws — all errors are captured in $lastError.
 *  - Never creates approved obligations — all items return with status='suggested'.
 *  - source_excerpt must be traceable to the originating chunk text.
 *  - due_rule must come from the same clause/paragraph as the obligation.
 */
class GeminiObligationExtractor implements ObligationExtractorInterface
{
    private ?string $lastError = null;
    private string  $model;
    private int     $timeout;
    private int     $maxChunkChars;
    private ?int    $maxChunksPerDocument;
    private string  $chunkSelectionMode;
    private bool    $redactSensitive;
    private ?string $apiKey;

    /** Runtime statistics from the last extract() call. */
    private array $extractionStats = [];

    // Valid enum values — items outside these are normalised or dropped
    private const VALID_PRIORITIES  = ['low', 'medium', 'high', 'critical'];
    private const VALID_RECURRENCES = ['Única', 'Mensal', 'Trimestral', 'Semestral', 'Anual', 'Sob demanda', 'Outro'];
    private const VALID_AREAS       = [
        'Jurídico', 'Gestão', 'Emissões', 'Financeiro',
        'Escrituração', 'Compliance', 'Risco', 'Engenharia', 'Outro',
    ];

    public function __construct(
        private SensitiveDataRedactor $redactor,
        private ObligationExtractionValidator $validator,
    ) {
        $this->apiKey               = config('obligations.gemini.api_key');
        $this->model                = config('obligations.gemini.model', 'gemini-2.5-flash');
        $this->timeout              = config('obligations.gemini.timeout', 30);
        $this->maxChunkChars        = config('obligations.gemini.max_chunk_chars', 8000);
        $this->maxChunksPerDocument = config('obligations.gemini.max_chunks_per_document'); // null = no limit
        $this->chunkSelectionMode   = config('obligations.gemini.chunk_selection_mode', 'all');
        $this->redactSensitive      = config('obligations.gemini.redact_sensitive', true);
    }

    // ── Interface ─────────────────────────────────────────────────────────────

    public function getProviderName(): string { return 'gemini'; }
    public function getModelName(): string    { return $this->model; }
    public function getLastError(): ?string   { return $this->lastError; }

    /**
     * Returns runtime statistics populated after extract() completes.
     * Includes chunk counts, raw/created/skipped obligation counts and skip reasons.
     */
    public function getExtractionStats(): array { return $this->extractionStats; }

    public function extract(TermDocument $document): array
    {
        $this->lastError      = null;
        $this->extractionStats = [];
        $startedAt            = now();

        // ── Pre-flight ────────────────────────────────────────────────────────
        if (empty($this->apiKey)) {
            $this->lastError = 'GEMINI_API_KEY is not configured. Set GEMINI_API_KEY in .env and run: php artisan config:clear';
            $this->extractionStats = [
                'total_chunks_available'    => 0,
                'chunks_selected'           => 0,
                'chunks_processed'          => 0,
                'chunk_selection_mode'      => $this->chunkSelectionMode,
                'max_chunks_limit'          => $this->maxChunksPerDocument,
                'gemini_api_key_configured' => false,
                'obligations_returned'      => 0,
                'obligations_created'       => 0,
                'obligations_skipped'       => 0,
                'skipped_reasons'           => [],
                'started_at'               => $startedAt->toIso8601String(),
                'finished_at'              => now()->toIso8601String(),
            ];
            Log::error('[GeminiExtractor] API key ausente.', ['document_id' => $document->id]);
            return [];
        }

        $text = $document->extracted_text ?? '';
        if (strlen(trim($text)) < 200) {
            $this->lastError = 'Texto extraído insuficiente para análise (mínimo 200 caracteres). Processe o documento antes de gerar obrigações.';
            return [];
        }

        // ── Chunk groups ──────────────────────────────────────────────────────
        $availableGroups = $this->buildChunkGroups($document);
        $groups          = $this->selectChunkGroups($availableGroups);

        Log::info('[GeminiExtractor] Iniciando extração.', [
            'operation_id'           => $document->operation_id,
            'document_id'            => $document->id,
            'model'                  => $this->model,
            'chunk_selection_mode'   => $this->chunkSelectionMode,
            'total_chunks_available' => count($availableGroups),
            'chunks_selected'        => count($groups),
            'max_chunks_limit'       => $this->maxChunksPerDocument,
        ]);

        // ── Process each group ────────────────────────────────────────────────
        $allObligations  = [];
        $allSkipped      = [];
        $chunksProcessed = 0;
        $rawCount        = 0;

        foreach ($groups as $index => $groupText) {
            // Redact PII/sensitive data before sending to external API
            $processedText = $this->redactSensitive
                ? $this->redactor->redact($groupText)
                : $groupText;

            try {
                $results   = $this->callGemini($processedText, $index + 1, count($groups));
                $rawCount += count($results);

                [$valid, $skipped] = $this->validateItems($results, $processedText);
                $allObligations    = array_merge($allObligations, $valid);
                $allSkipped        = array_merge($allSkipped, $skipped);
                $chunksProcessed++;
            } catch (Throwable $e) {
                Log::warning('[GeminiExtractor] Falha no chunk ' . ($index + 1) . '.', [
                    'document_id' => $document->id,
                    'error'       => $e->getMessage(),
                ]);
                $allSkipped[] = [
                    'reason' => 'chunk_api_error',
                    'detail' => $e->getMessage(),
                    'chunk'  => $index + 1,
                ];
                // Continue with remaining chunks — partial results are still valuable
            }
        }

        // ── Deduplicate, preferring higher-quality candidates ─────────────────
        $deduplicated = $this->deduplicate($allObligations);

        $this->extractionStats = [
            'total_chunks_available'    => count($availableGroups),
            'chunks_selected'           => count($groups),
            'chunks_processed'          => $chunksProcessed,
            'chunk_selection_mode'      => $this->chunkSelectionMode,
            'max_chunks_limit'          => $this->maxChunksPerDocument,
            'gemini_api_key_configured' => true,
            'obligations_returned'      => $rawCount,
            'obligations_created'       => count($deduplicated),
            'obligations_skipped'       => count($allSkipped),
            'skipped_reasons'           => array_values($allSkipped),
            'started_at'                => $startedAt->toIso8601String(),
            'finished_at'               => now()->toIso8601String(),
        ];

        Log::info('[GeminiExtractor] Extração concluída.', [
            'document_id'  => $document->id,
            'raw'          => $rawCount,
            'deduplicated' => count($deduplicated),
            'skipped'      => count($allSkipped),
        ]);

        if (empty($deduplicated) && $this->lastError === null) {
            $this->lastError = 'Nenhuma obrigação identificada no texto. Verifique se o documento é um Termo de Securitização válido.';
        }

        return $deduplicated;
    }

    // ── Chunking ──────────────────────────────────────────────────────────────

    /**
     * Returns an array of text strings, each ≤ maxChunkChars.
     * Prefers TermDocumentChunk records; falls back to splitting raw text.
     */
    private function buildChunkGroups(TermDocument $document): array
    {
        $chunks = $document->chunks()->orderBy('sort_order')->pluck('content')->toArray();

        if (empty($chunks)) {
            $chunks = $this->splitRawText($document->extracted_text ?? '');
        }

        $groups  = [];
        $current = '';

        foreach ($chunks as $chunk) {
            $chunkText = trim($chunk);
            if ($chunkText === '') {
                continue;
            }

            if ($current !== '' && strlen($current) + strlen($chunkText) + 2 > $this->maxChunkChars) {
                $groups[]  = $current;
                $current   = $chunkText;
            } else {
                $current .= ($current !== '' ? "\n\n" : '') . $chunkText;
            }
        }

        if ($current !== '') {
            $groups[] = $current;
        }

        return $groups ?: [$document->extracted_text ?? ''];
    }

    /**
     * Selects and orders chunk groups for processing.
     *
     * 'all'      → document order; apply max limit if set.
     * 'relevant' → sort by obligation-keyword density before applying limit,
     *              so the most obligation-dense sections are prioritised when
     *              max_chunks_per_document restricts the total.
     *
     * When max_chunks_per_document is null ALL groups are returned.
     */
    private function selectChunkGroups(array $groups): array
    {
        if ($this->chunkSelectionMode === 'relevant') {
            usort($groups, fn (string $a, string $b) =>
                $this->scoreChunkGroup($b) <=> $this->scoreChunkGroup($a)
            );
        }

        if ($this->maxChunksPerDocument === null || $this->maxChunksPerDocument <= 0) {
            return $groups;
        }

        return array_slice($groups, 0, $this->maxChunksPerDocument);
    }

    /**
     * Returns a normalised density score (hits per 1 000 chars) counting
     * Portuguese legal-obligation keywords in the text.
     * Used only when chunk_selection_mode = 'relevant'.
     */
    private function scoreChunkGroup(string $text): float
    {
        static $keywords = [
            'deverá', 'deve', 'obrigação', 'obrigações', 'prazo', 'relatório',
            'enviar', 'apresentar', 'entregar', 'comunicar', 'notificar',
            'cláusula', 'dias', 'úteis', 'mensal', 'trimestral', 'semestral',
            'anual', 'covenant', 'inadimplemento', 'vencimento', 'assembleia',
            'assembléia', 'fiduciário', 'emissora', 'garantia', 'auditado',
            'comunicação', 'informação', 'monitoramento', 'verificação',
        ];

        $lower = mb_strtolower($text, 'UTF-8');
        $hits  = 0;
        foreach ($keywords as $kw) {
            $hits += substr_count($lower, $kw);
        }

        return $hits / max(1, strlen($text) / 1000);
    }

    private function splitRawText(string $text): array
    {
        $paragraphs = preg_split('/\n{2,}/', $text) ?: [];
        $chunks     = [];
        $current    = '';

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                continue;
            }
            if ($current !== '' && strlen($current) + strlen($para) + 2 > $this->maxChunkChars) {
                $chunks[]  = $current;
                $current   = $para;
            } else {
                $current .= ($current !== '' ? "\n\n" : '') . $para;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    // ── Gemini API call ───────────────────────────────────────────────────────

    /**
     * Calls the Gemini API with the already-processed (redacted) chunk text.
     *
     * @throws \RuntimeException on HTTP or JSON parse failure
     */
    private function callGemini(string $processedText, int $chunkIndex, int $totalChunks): array
    {
        $prompt = $this->buildPrompt($processedText, $chunkIndex, $totalChunks);

        $url = sprintf(
            '%s/%s:generateContent?key=%s',
            config('obligations.gemini.base_url'),
            $this->model,
            $this->apiKey
        );

        $response = Http::timeout($this->timeout)
            ->post($url, [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature'      => 0.1,
                    'maxOutputTokens'  => 8192,
                ],
            ]);

        if ($response->failed()) {
            $status = $response->status();
            Log::error('[GeminiExtractor] HTTP error.', [
                'status' => $status,
                'body'   => substr($response->body(), 0, 500),
            ]);
            throw new \RuntimeException("Gemini API retornou HTTP $status.");
        }

        $data    = $response->json();
        $rawText = data_get($data, 'candidates.0.content.parts.0.text', '');

        if (empty(trim($rawText))) {
            $finishReason = data_get($data, 'candidates.0.finishReason', 'unknown');
            throw new \RuntimeException("Gemini retornou resposta vazia (finishReason=$finishReason).");
        }

        $decoded = json_decode($rawText, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            Log::warning('[GeminiExtractor] JSON inválido na resposta.', ['raw' => substr($rawText, 0, 500)]);
            throw new \RuntimeException('Resposta da IA não é JSON válido: ' . json_last_error_msg());
        }

        return $decoded['obligations'] ?? [];
    }

    // ── Prompt ────────────────────────────────────────────────────────────────

    private function buildPrompt(string $text, int $chunkIndex, int $totalChunks): string
    {
        $chunkNote = $totalChunks > 1
            ? "Este é o segmento $chunkIndex de $totalChunks do documento. Extraia obrigações SOMENTE a partir deste segmento."
            : 'Este é o documento completo.';

        return <<<PROMPT
Você é um especialista em direito do mercado de capitais brasileiro, com foco em operações de securitização (CRI, CRA, Debêntures, Notas Comerciais).

Sua tarefa é extrair obrigações contratuais de um trecho de Termo de Securitização.

{$chunkNote}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRAS DE FUNDAMENTAÇÃO — CRÍTICAS — LEIA COM ATENÇÃO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. EXTRAIA SOMENTE o que está explicitamente escrito neste trecho. Não use conhecimento externo, prática de mercado nem inferências.
2. NÃO crie obrigações a partir de definições, conceitos ou descrição de fluxos normais de pagamento.
3. NÃO combine elementos de cláusulas diferentes:
   — Se a obrigação está na Cláusula 8 e o prazo está na Cláusula 12, NÃO crie essa obrigação.
   — O `due_rule` deve vir OBRIGATORIAMENTE da mesma frase ou parágrafo que descreve a obrigação.
   — NÃO atribua responsável, prazo, evidência ou periodicidade de uma cláusula a uma obrigação de outra cláusula.
4. `due_rule` deve reproduzir o prazo EXATAMENTE como escrito no trecho-fonte, sem reescrever.
   — Se o prazo não estiver na mesma frase/parágrafo da obrigação, defina `due_rule` como null.
   — Se o prazo for null, adicione explicação em `review_notes`.
   — Se `due_rule` menciona "Dia Útil" ou "Dias Úteis", o `source_excerpt` DEVE conter explicitamente "Dia Útil" ou "Dias Úteis". Nunca escreva "Até o 10º Dia Útil" se o trecho diz "30º dia após o término" — são tipos de prazo incompatíveis.
   — Se `due_rule` contém qualquer número (ex.: 10, 30, 5), esse exato número DEVE aparecer no `source_excerpt`.
5. `source_excerpt` deve ser uma citação LITERAL e VERBATIM do texto fornecido (máximo 300 caracteres).
   — Não parafrasear. Não resumir. Não reescrever.
   — O trecho citado DEVE conter a obrigação principal ou o prazo que a justifica.
   — Se não conseguir citar literalmente o texto, omita a obrigação inteiramente.
6. Se `responsible_party` não estiver explícito na mesma cláusula, defina como null e use `confidence_score` ≤ 0.65.
7. Se `required_evidence` não for exigida explicitamente no texto, defina como null.
8. Se a obrigação for ambígua ou incerta, explique em `review_notes` e use `confidence_score` < 0.60.
9. Se este trecho não contiver nenhuma obrigação clara, retorne: {"obligations": []}
10. NÃO duplique obrigações. Se a mesma obrigação aparecer em mais de uma cláusula, extraia apenas uma vez.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EXEMPLOS DE COMPORTAMENTO CORRETO E INCORRETO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

EXEMPLO 1 — Nunca combine prazo de cláusula diferente:

  Trecho da Cláusula 8.1:
    "A Emissora deverá elaborar Relatório Mensal de Acompanhamento da Operação."
  Trecho da Cláusula 12.3 (separado):
    "Os documentos deverão ser entregues em até 10 (dez) Dias Úteis contados do encerramento do mês."

  ❌ ERRADO — combinar prazo de outra cláusula:
    due_rule: "Até o 10º Dia Útil de cada mês"
    source_excerpt: "A Emissora deverá elaborar Relatório Mensal de Acompanhamento da Operação."
    (O prazo da Cláusula 12.3 não pode ser atribuído à obrigação da Cláusula 8.1)

  ✅ CORRETO — para a Cláusula 8.1 isolada:
    due_rule: null
    review_notes: "Prazo não especificado neste trecho. Verificar se há cláusula complementar."
    source_excerpt: "A Emissora deverá elaborar Relatório Mensal de Acompanhamento da Operação."

  ✅ CORRETO — se a cláusula contiver prazo na mesma frase:
    Texto: "A Emissora deverá elaborar Relatório Mensal de Acompanhamento, em até 30 (trinta) dias contados do encerramento do mês."
    due_rule: "em até 30 (trinta) dias contados do encerramento do mês"
    source_excerpt: "A Emissora deverá elaborar Relatório Mensal de Acompanhamento, em até 30 (trinta) dias contados do encerramento do mês."

EXEMPLO 2 — source_excerpt deve ser literal:

  Texto real: "a Emissora elaborará relatório mensal de acompanhamento da operação"

  ❌ ERRADO — conteúdo inventado:
    source_excerpt: "posição da carteira, inadimplência e indicadores contratuais"
    (Esses termos não estão no texto — são inferência do modelo)

  ✅ CORRETO — citação literal:
    source_excerpt: "a Emissora elaborará relatório mensal de acompanhamento da operação"

EXEMPLO 3 — Obrigação sem prazo explícito:

  Texto: "A Emissora deverá comunicar ao Agente Fiduciário qualquer Evento de Inadimplemento."

  ✅ CORRETO:
    due_rule: null
    recurrence: "Sob demanda"
    review_notes: "Prazo não especificado neste trecho."
    source_excerpt: "A Emissora deverá comunicar ao Agente Fiduciário qualquer Evento de Inadimplemento."

EXEMPLO 4 — CRÍTICO: nunca use número ou tipo de prazo diferente do que está no trecho:

  source_excerpt: "até o 30º (trigésimo) dia após o término de cada mês, relatório mensal de acompanhamento"

  ❌ ERRADO — número E tipo de prazo incompatíveis:
    due_rule: "Até o 10º dia útil de cada mês"
    Razão 1: O trecho diz "30", not "10" — números diferentes.
    Razão 2: O trecho diz "dia após", não "Dia Útil" — tipos de prazo diferentes.

  ✅ CORRETO — prazo copiado literalmente do trecho:
    due_rule: "até o 30º (trigésimo) dia após o término de cada mês"
    (O número "30" e o tipo "dia após" estão ambos no trecho de origem.)

  REGRA ABSOLUTA 1: Se due_rule contém um número (ex.: 10, 30, 5), esse exato número
  DEVE aparecer no source_excerpt. Se não aparecer, defina due_rule como null.

  REGRA ABSOLUTA 2: Se due_rule menciona "Dia Útil" ou "Dias Úteis", o source_excerpt
  DEVE conter explicitamente "Dia Útil" ou "Dias Úteis". "Dia Útil" de outra cláusula
  não pode ser transferido para uma obrigação cujo trecho não menciona "Dia Útil".

EXEMPLO 5 — "Dia Útil" no prazo exige "Dia Útil" no trecho:

  Texto da Cláusula 8.1: "A Emissora elaborará relatório mensal de acompanhamento."
  Texto da Cláusula 12.3: "Os documentos deverão ser entregues em até 10 (dez) Dias Úteis."

  ❌ ERRADO — "Dias Úteis" da Cláusula 12.3 importado para Cláusula 8.1:
    source_excerpt: "A Emissora elaborará relatório mensal de acompanhamento."
    due_rule: "Até o 10º Dia Útil de cada mês"
    (O trecho da Cláusula 8.1 não menciona "Dia Útil" — este prazo é de outra cláusula.)

  ✅ CORRETO — sem prazo no trecho, due_rule é null:
    source_excerpt: "A Emissora elaborará relatório mensal de acompanhamento."
    due_rule: null
    review_notes: "Prazo não especificado neste trecho."

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TIPOS DE OBRIGAÇÕES A EXTRAIR
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- Relatórios periódicos (mensal, trimestral, semestral, anual)
- Relatórios de destinação de recursos e medição de obras
- Demonstrações financeiras auditadas/revisadas
- Obrigações de informação e comunicação ao Agente Fiduciário
- Obrigações de comunicação a titulares, investidores e reguladores (CVM, B3)
- Verificações e monitoramentos do Agente Fiduciário
- Controle de Fundos de Reserva, Despesas, Juros, Obras
- Monitoramento de recebíveis, lastro e garantias
- Covenants financeiros e operacionais
- Eventos de vencimento antecipado e inadimplemento
- Convocação e participação em assembleias
- Atualização cadastral e documental da operação
- Obrigações disparadas por solicitação de autoridades

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CAMPOS E REGRAS DE PREENCHIMENTO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- title: Título conciso em português imperativo (ex.: "Enviar relatório mensal ao Agente Fiduciário").
- obligation_type: Categoria da obrigação (ex.: "Relatório Periódico", "Comunicação", "Covenant").
- description: Descrição completa baseada exclusivamente no texto-fonte.
- responsible_party: Parte responsável EXPLÍCITA no texto (ex.: "Emissora", "Agente Fiduciário"). null se não explícito.
- responsible_area: Uma de: Jurídico, Gestão, Emissões, Financeiro, Escrituração, Compliance, Risco, Engenharia, Outro.
- recurrence: Uma de: Única, Mensal, Trimestral, Semestral, Anual, Sob demanda, Outro.
- due_rule: Prazo LITERAL do texto-fonte da mesma frase/parágrafo. null se ausente ou em outra cláusula.
- due_date: Data fixa YYYY-MM-DD. null se recorrente ou sem data definida.
- priority: "low", "medium", "high" ou "critical".
- required_evidence: Evidência exigida explicitamente no texto. null se não especificada.
- source_clause: Referência da cláusula (ex.: "Cláusula 8.1.2"). null se não identificável.
- source_page: Número da página. null se desconhecido.
- source_excerpt: Citação LITERAL do texto (máximo 300 chars). OBRIGATÓRIO. Deve provar a obrigação.
- confidence_score: 0.0–1.0. Use ≥0.80 para explícita, 0.60–0.79 para inferida, <0.60 para incerta.
- review_notes: Observações para o revisor (prazos ausentes, ambiguidades, condições). null se desnecessário.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SCHEMA JSON OBRIGATÓRIO — RETORNE SOMENTE JSON
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

{
  "obligations": [
    {
      "title": "string",
      "obligation_type": "string",
      "description": "string",
      "responsible_party": "string|null",
      "responsible_area": "string",
      "recurrence": "string",
      "due_rule": "string|null",
      "due_date": "YYYY-MM-DD|null",
      "priority": "low|medium|high|critical",
      "required_evidence": "string|null",
      "source_clause": "string|null",
      "source_page": "integer|null",
      "source_excerpt": "string",
      "confidence_score": 0.0,
      "review_notes": "string|null"
    }
  ]
}

Se não houver obrigações neste trecho: {"obligations": []}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TEXTO DO TERMO DE SECURITIZAÇÃO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{$text}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PROMPT;
    }

    // ── Validation ────────────────────────────────────────────────────────────

    /**
     * Validates and normalises raw obligation items from Gemini.
     * Performs source-grounding and due_rule grounding checks.
     *
     * @param  array  $items      Raw items from Gemini JSON
     * @param  string $chunkText  The processed (redacted) text sent to Gemini
     * @return array{0: array, 1: array}  [valid items, skipped item details]
     */
    private function validateItems(array $items, string $chunkText): array
    {
        $valid   = [];
        $skipped = [];

        foreach ($items as $i => $item) {
            if (! is_array($item)) {
                $skipped[] = ['index' => $i, 'reason' => 'not_array', 'title' => null];
                continue;
            }

            $title = trim($item['title'] ?? '');

            // ── Required string fields ─────────────────────────────────────────
            foreach (['title', 'obligation_type', 'description'] as $field) {
                if (empty(trim((string) ($item[$field] ?? '')))) {
                    Log::debug("[GeminiExtractor] Item $i ignorado: campo '$field' vazio.");
                    $skipped[] = ['index' => $i, 'reason' => "missing_$field", 'title' => $title ?: null];
                    continue 2;
                }
            }

            // ── source_excerpt required ────────────────────────────────────────
            $sourceExcerpt = trim($item['source_excerpt'] ?? '');
            if (empty($sourceExcerpt)) {
                Log::debug("[GeminiExtractor] Item '$title' ignorado: source_excerpt vazio.");
                $skipped[] = ['index' => $i, 'reason' => 'missing_source_excerpt', 'title' => $title];
                continue;
            }

            // ── Source grounding check ─────────────────────────────────────────
            if (! $this->isExcerptGrounded($sourceExcerpt, $chunkText)) {
                Log::warning("[GeminiExtractor] Item '$title' ignorado: source_excerpt não encontrado no chunk.", [
                    'excerpt_preview' => substr($sourceExcerpt, 0, 100),
                ]);
                $skipped[] = [
                    'index'  => $i,
                    'reason' => 'source_excerpt_not_in_chunk',
                    'title'  => $title,
                    'detail' => substr($sourceExcerpt, 0, 120),
                ];
                continue;
            }

            // ── Normalise enums ────────────────────────────────────────────────
            $priority = strtolower(trim($item['priority'] ?? 'medium'));
            if (! in_array($priority, self::VALID_PRIORITIES, true)) {
                $priority = 'medium';
            }

            $recurrence = trim($item['recurrence'] ?? 'Outro');
            if (! in_array($recurrence, self::VALID_RECURRENCES, true)) {
                $recurrence = 'Outro';
            }

            $area = trim($item['responsible_area'] ?? 'Outro');
            if (! in_array($area, self::VALID_AREAS, true)) {
                $area = 'Outro';
            }

            // ── Normalise confidence_score ─────────────────────────────────────
            $score = is_numeric($item['confidence_score'] ?? null)
                ? max(0.0, min(1.0, (float) $item['confidence_score']))
                : null;

            // ── due_rule strict numeric validation ────────────────────────────
            // If due_rule contains a number (e.g. "10") that does NOT appear in
            // source_excerpt, the obligation is skipped entirely — a mismatch
            // between "10" and "30" is a definitive cross-clause contamination.
            $dueRule     = isset($item['due_rule']) ? trim($item['due_rule']) : null;
            $reviewNotes = isset($item['review_notes']) ? trim($item['review_notes']) : null;

            if ($dueRule !== null) {
                $dueRuleValidation = $this->validator->validateDueRule($dueRule, $sourceExcerpt);

                if (! $dueRuleValidation['valid']) {
                    Log::warning("[GeminiExtractor] Item '$title' ignorado: due_rule inválido.", [
                        'due_rule' => $dueRule,
                        'excerpt'  => substr($sourceExcerpt, 0, 150),
                        'detail'   => $dueRuleValidation['detail'] ?? '',
                    ]);
                    $skipped[] = [
                        'index'                  => $i,
                        'reason'                 => $dueRuleValidation['reason'],
                        'title'                  => $title,
                        'due_rule'               => $dueRule,
                        'detail'                 => $dueRuleValidation['detail'] ?? null,
                        'source_excerpt_preview' => substr($sourceExcerpt, 0, 150),
                    ];
                    continue; // skip — do not save misleading obligation
                }
            }

            // ── Normalise source_clause ────────────────────────────────────────
            $sourceClause = isset($item['source_clause']) ? trim($item['source_clause']) : null;
            if ($sourceClause === '') {
                $sourceClause = null;
            }

            // ── Cap confidence when source quality is weak ─────────────────────
            // Without a source_clause we cannot verify which clause the obligation
            // came from.  With an unverifiable clause AND a deadline, the risk of
            // cross-clause contamination is highest — cap confidence aggressively.
            if ($score !== null) {
                if ($sourceClause === null) {
                    $score = min($score, 0.70);
                }
                if ($sourceClause === null && $dueRule !== null) {
                    $score = min($score, 0.60);
                }
            }

            // ── Normalise due_date ─────────────────────────────────────────────
            $dueDate = null;
            if (! empty($item['due_date'])) {
                $parsed = date_create_from_format('Y-m-d', $item['due_date']);
                if ($parsed) {
                    $dueDate = $item['due_date'];
                }
            }

            // ── Normalise source_page ──────────────────────────────────────────
            $sourcePage = isset($item['source_page']) && is_numeric($item['source_page'])
                ? (int) $item['source_page']
                : null;

            $valid[] = [
                'title'             => substr(trim($item['title']), 0, 255),
                'obligation_type'   => substr(trim($item['obligation_type']), 0, 255),
                'description'       => trim($item['description']),
                'responsible_party' => isset($item['responsible_party']) ? substr(trim($item['responsible_party']), 0, 255) : null,
                'responsible_area'  => $area,
                'recurrence'        => $recurrence,
                'due_rule'          => $dueRule,
                'due_date'          => $dueDate,
                'priority'          => $priority,
                'required_evidence' => isset($item['required_evidence']) ? trim($item['required_evidence']) : null,
                'source_clause'     => $sourceClause !== null ? substr($sourceClause, 0, 255) : null,
                'source_page'       => $sourcePage,
                'source_excerpt'    => substr($sourceExcerpt, 0, 1000),
                'confidence_score'  => $score,
                'review_notes'      => $reviewNotes ?: null,
                'ai_provider'       => $this->getProviderName(),
                'ai_model'          => $this->model,
            ];
        }

        return [$valid, $skipped];
    }

    // ── Source grounding helpers ──────────────────────────────────────────────

    /**
     * Normalises text for string-comparison purposes:
     * lowercase → strip diacritics → remove punctuation → collapse whitespace.
     */
    private function normalizeForComparison(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        // Separate base characters from combining diacritical marks (ext-intl)
        if (class_exists('\Normalizer')) {
            $nfd = \Normalizer::normalize($text, \Normalizer::FORM_D);
            if ($nfd !== false) {
                $text = preg_replace('/\p{Mn}/u', '', $nfd) ?? $text;
            }
        }

        // Replace punctuation with spaces; keep alphanumerics and spaces
        $text = preg_replace('/[^\w\s]/u', ' ', $text) ?? $text;

        // Collapse whitespace
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Returns true if source_excerpt can be traced back to the chunk text.
     *
     * Matching strategy (in order):
     *  1. Exact normalised substring match.
     *  2. Normalised 80-char prefix match.
     *  3. ≥70% of significant words (≥4 chars) appear in chunk.
     */
    private function isExcerptGrounded(string $excerpt, string $chunkText): bool
    {
        if (empty(trim($excerpt)) || empty(trim($chunkText))) {
            return false;
        }

        $normExcerpt = $this->normalizeForComparison($excerpt);
        $normChunk   = $this->normalizeForComparison($chunkText);

        // 1. Exact normalised match
        if (str_contains($normChunk, $normExcerpt)) {
            return true;
        }

        // 2. Loose prefix match — first 80 normalised chars
        $prefix = substr($normExcerpt, 0, 80);
        if (strlen($prefix) >= 20 && str_contains($normChunk, $prefix)) {
            return true;
        }

        // 3. Word-overlap — ≥70% of significant words appear in chunk
        $words       = array_filter(explode(' ', $normExcerpt), fn ($w) => strlen($w) >= 4);
        $significant = array_values($words);

        if (count($significant) < 3) {
            // Too few words to verify — accept to avoid false negatives on short excerpts
            return true;
        }

        $matchCount = 0;
        foreach ($significant as $word) {
            if (str_contains($normChunk, $word)) {
                $matchCount++;
            }
        }

        return ($matchCount / count($significant)) >= 0.70;
    }

    // ── Deduplication ─────────────────────────────────────────────────────────

    /**
     * Deduplicates obligations by key, keeping the higher-quality candidate
     * when the same logical obligation appears more than once (definition section,
     * main clause, repeated reference, annex).
     */
    private function deduplicate(array $obligations): array
    {
        $seenIndex = []; // key => index in $result
        $result    = [];

        foreach ($obligations as $ob) {
            $key = $this->deduplicationKey($ob);

            if (! isset($seenIndex[$key])) {
                $seenIndex[$key] = count($result);
                $result[]        = $ob;
            } else {
                $existingIdx = $seenIndex[$key];
                if ($this->isBetterCandidate($ob, $result[$existingIdx])) {
                    Log::debug('[GeminiExtractor] Duplicata substituída por candidato de maior qualidade.', [
                        'title'     => $ob['title'],
                        'old_score' => $result[$existingIdx]['confidence_score'] ?? null,
                        'new_score' => $ob['confidence_score'] ?? null,
                    ]);
                    $result[$existingIdx] = $ob;
                } else {
                    Log::debug('[GeminiExtractor] Duplicata ignorada.', ['title' => $ob['title']]);
                }
            }
        }

        return array_values($result);
    }

    private function deduplicationKey(array $ob): string
    {
        // source_clause is intentionally excluded: the same obligation can appear in
        // the definition section (no clause), the main clause, and annexes — all
        // should be treated as one obligation, keeping the highest-quality candidate.
        return implode('|', [
            strtolower(trim($ob['title'])),
            strtolower(trim($ob['obligation_type'])),
            strtolower(trim($ob['responsible_party'] ?? '')),
            strtolower(trim($ob['recurrence'] ?? '')),
            strtolower(trim($ob['due_rule'] ?? '')),
        ]);
    }

    /**
     * Returns true if $new is a better candidate than $existing for the same dedup key.
     *
     * Preference order:
     *  1. Significantly higher confidence score (>5pp gap).
     *  2. Has source_clause when existing does not.
     *  3. Longer source_excerpt (>50 char gap) when clause availability is equal.
     */
    private function isBetterCandidate(array $new, array $existing): bool
    {
        $newScore      = (float) ($new['confidence_score'] ?? 0);
        $existingScore = (float) ($existing['confidence_score'] ?? 0);

        if ($newScore > $existingScore + 0.05) {
            return true;
        }

        $newHasClause      = ! empty($new['source_clause']);
        $existingHasClause = ! empty($existing['source_clause']);

        if ($newHasClause && ! $existingHasClause) {
            return true;
        }

        if ($newHasClause === $existingHasClause) {
            $newExcerptLen      = strlen($new['source_excerpt'] ?? '');
            $existingExcerptLen = strlen($existing['source_excerpt'] ?? '');
            if ($newExcerptLen > $existingExcerptLen + 50) {
                return true;
            }
        }

        return false;
    }
}
