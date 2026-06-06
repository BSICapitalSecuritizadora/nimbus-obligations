<?php

namespace Tests\Feature;

use App\Contracts\ObligationExtractorInterface;
use App\Models\ExtractedObligation;
use App\Models\Obligation;
use App\Models\Operation;
use App\Models\TermDocument;
use App\Services\GeminiObligationExtractor;
use App\Services\MockObligationExtractor;
use App\Services\ObligationExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ObligationExtractionTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeDocument(string $text = 'Some extracted text about obligations.'): TermDocument
    {
        $operation = Operation::create([
            'name'           => 'Test Op',
            'operation_type' => 'CRI',
            'status'         => 'active',
            'issue_date'     => now(),
            'total_amount'   => 1_000_000,
        ]);

        return TermDocument::create([
            'operation_id'      => $operation->id,
            'original_filename' => 'test.pdf',
            'stored_path'       => 'test/test.pdf',
            'mime_type'         => 'application/pdf',
            'file_size'         => 1024,
            'processing_status' => 'processed',
            'extracted_text'    => $text,
        ]);
    }

    // ── MockObligationExtractor ───────────────────────────────────────────────

    #[Test]
    public function mock_extractor_returns_suggestions(): void
    {
        $document  = $this->makeDocument('Relatório mensal de inadimplência deve ser enviado até o quinto dia útil.');
        $extractor = app(MockObligationExtractor::class);

        $results = $extractor->extract($document);

        $this->assertIsArray($results);
        $this->assertSame('mock', $extractor->getProviderName());
        $this->assertSame('keyword-patterns', $extractor->getModelName());
        $this->assertNull($extractor->getLastError());
    }

    #[Test]
    public function mock_extractor_is_bound_when_env_is_mock(): void
    {
        config(['obligations.extractor' => 'mock']);

        $extractor = app(ObligationExtractorInterface::class);

        $this->assertInstanceOf(MockObligationExtractor::class, $extractor);
    }

    // ── GeminiObligationExtractor ─────────────────────────────────────────────

    #[Test]
    public function gemini_extractor_is_bound_when_env_is_gemini(): void
    {
        config(['obligations.extractor' => 'gemini']);

        $extractor = app(ObligationExtractorInterface::class);

        $this->assertInstanceOf(GeminiObligationExtractor::class, $extractor);
    }

    #[Test]
    public function gemini_extractor_fails_gracefully_with_missing_api_key(): void
    {
        config([
            'obligations.extractor'      => 'gemini',
            'obligations.gemini.api_key' => null,
        ]);

        $document  = $this->makeDocument('Some text that is long enough to be processed by the AI extractor.');
        $extractor = app(GeminiObligationExtractor::class);

        $results = $extractor->extract($document);

        $this->assertSame([], $results);
        $this->assertNotNull($extractor->getLastError());
        $this->assertStringContainsStringIgnoringCase('GEMINI_API_KEY', $extractor->getLastError());
    }

    #[Test]
    public function gemini_extractor_fails_gracefully_with_http_error(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'Quota exceeded']], 429),
        ]);

        config([
            'obligations.gemini.api_key' => 'fake-key-for-testing',
        ]);

        $longText  = str_repeat('Cláusula 1: O securitizador deve enviar relatório mensal. ', 50);
        $document  = $this->makeDocument($longText);
        $extractor = app(GeminiObligationExtractor::class);

        $results = $extractor->extract($document);

        $this->assertSame([], $results);
        $this->assertNotNull($extractor->getLastError());
    }

    #[Test]
    public function gemini_extractor_fails_gracefully_with_invalid_json(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'not valid json at all {{{']]],
                ]],
            ], 200),
        ]);

        config([
            'obligations.gemini.api_key' => 'fake-key-for-testing',
        ]);

        $longText  = str_repeat('Cláusula 1: O securitizador deve enviar relatório mensal. ', 50);
        $document  = $this->makeDocument($longText);
        $extractor = app(GeminiObligationExtractor::class);

        $results = $extractor->extract($document);

        $this->assertIsArray($results);
        $this->assertNotNull($extractor->getLastError());
    }

    // ── Source grounding ──────────────────────────────────────────────────────

    /** Build a fake Gemini HTTP response from an obligations array. */
    private function fakeGeminiResponse(array $obligations): array
    {
        return [
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode(['obligations' => $obligations]),
                    ]],
                ],
            ]],
        ];
    }

    #[Test]
    public function gemini_obligation_with_ungrounded_source_excerpt_is_skipped(): void
    {
        // Chunk text contains only Portuguese but WITHOUT the hallucinated phrases below
        $chunkText = str_repeat('A Emissora elaborara relatorio mensal de acompanhamento da operacao. ', 10);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->fakeGeminiResponse([[
                    'title'             => 'Enviar relatório mensal',
                    'obligation_type'   => 'Relatório',
                    'description'       => 'Enviar relatório mensal.',
                    'responsible_party' => 'Emissora',
                    'responsible_area'  => 'Gestão',
                    'recurrence'        => 'Mensal',
                    'due_rule'          => null,
                    'due_date'          => null,
                    'priority'          => 'medium',
                    'required_evidence' => null,
                    'source_clause'     => 'Cláusula 8.1',
                    'source_page'       => null,
                    // This excerpt does NOT appear in $chunkText — hallucinated
                    'source_excerpt'    => 'posição da carteira inadimplência e indicadores contratuais que não existem no texto',
                    'confidence_score'  => 0.85,
                    'review_notes'      => null,
                ]]),
                200
            ),
        ]);

        config(['obligations.gemini.api_key' => 'fake-key-for-testing']);

        $document  = $this->makeDocument($chunkText);
        $extractor = app(GeminiObligationExtractor::class);

        $results = $extractor->extract($document);

        $this->assertSame([], $results);

        $stats   = $extractor->getExtractionStats();
        $reasons = array_column($stats['skipped_reasons'], 'reason');
        $this->assertContains('source_excerpt_not_in_chunk', $reasons);
    }

    #[Test]
    public function gemini_obligation_with_due_rule_number_absent_from_excerpt_is_skipped(): void
    {
        // Chunk does not mention "10" — source_excerpt below contains no integers
        $chunkText = str_repeat('A Emissora elaborará relatório mensal de acompanhamento da operação conforme previsto. ', 10);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->fakeGeminiResponse([[
                    'title'             => 'Elaborar relatório mensal',
                    'obligation_type'   => 'Relatório',
                    'description'       => 'Elaborar relatório mensal.',
                    'responsible_party' => 'Emissora',
                    'responsible_area'  => 'Gestão',
                    'recurrence'        => 'Mensal',
                    // "10" is NOT present in source_excerpt
                    'due_rule'          => 'Até o 10º Dia Útil de cada mês',
                    'due_date'          => null,
                    'priority'          => 'medium',
                    'required_evidence' => null,
                    'source_clause'     => 'Cláusula 8.1',
                    'source_page'       => null,
                    'source_excerpt'    => 'A Emissora elaborará relatório mensal de acompanhamento da operação conforme previsto.',
                    'confidence_score'  => 0.90,
                    'review_notes'      => null,
                ]]),
                200
            ),
        ]);

        config(['obligations.gemini.api_key' => 'fake-key-for-testing']);

        $document  = $this->makeDocument($chunkText);
        $extractor = app(GeminiObligationExtractor::class);

        $results = $extractor->extract($document);

        // Must be skipped — "10" in due_rule does not appear in source_excerpt integers
        $this->assertSame([], $results);

        $stats   = $extractor->getExtractionStats();
        $reasons = array_column($stats['skipped_reasons'], 'reason');
        $this->assertContains('unsupported_due_rule', $reasons);
    }

    #[Test]
    public function gemini_obligation_with_grounded_excerpt_and_due_rule_is_accepted(): void
    {
        $clauseText = 'A Emissora devera elaborar relatorio mensal de acompanhamento em ate 30 dias contados do encerramento do mes e enviar ao Agente Fiduciario.';
        $chunkText  = str_repeat($clauseText . ' ', 15);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->fakeGeminiResponse([[
                    'title'             => 'Elaborar e enviar relatório mensal ao Agente Fiduciário',
                    'obligation_type'   => 'Relatório',
                    'description'       => 'Elaborar relatório mensal e enviar ao Agente Fiduciário.',
                    'responsible_party' => 'Emissora',
                    'responsible_area'  => 'Gestão',
                    'recurrence'        => 'Mensal',
                    'due_rule'          => 'ate 30 dias contados do encerramento do mes',
                    'due_date'          => null,
                    'priority'          => 'medium',
                    'required_evidence' => null,
                    'source_clause'     => 'Cláusula 8.1',
                    'source_page'       => null,
                    'source_excerpt'    => $clauseText,
                    'confidence_score'  => 0.92,
                    'review_notes'      => null,
                ]]),
                200
            ),
        ]);

        config(['obligations.gemini.api_key' => 'fake-key-for-testing']);

        $document  = $this->makeDocument($chunkText);
        $extractor = app(GeminiObligationExtractor::class);

        $results = $extractor->extract($document);

        $this->assertCount(1, $results);
        $this->assertSame('Elaborar e enviar relatório mensal ao Agente Fiduciário', $results[0]['title']);
        $this->assertSame(0.92, $results[0]['confidence_score']);
        $this->assertNull($results[0]['review_notes']);
        $this->assertSame('gemini', $results[0]['ai_provider']);

        $stats = $extractor->getExtractionStats();
        $this->assertSame(1, $stats['obligations_created']);
        $this->assertSame(0, $stats['obligations_skipped']);
    }

    // ── Due-rule numeric validation ───────────────────────────────────────────

    #[Test]
    public function gemini_due_rule_with_conflicting_number_is_skipped(): void
    {
        // source_excerpt says "30" but due_rule says "10" — definitive cross-clause mismatch
        $sourceExcerpt = 'ate o 30 trigesimo dia apos o termino de cada mes relatorio mensal de acompanhamento';
        $chunkText     = str_repeat($sourceExcerpt . ' ', 15);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->fakeGeminiResponse([[
                    'title'             => 'Elaborar relatório mensal',
                    'obligation_type'   => 'Relatório',
                    'description'       => 'Elaborar relatório mensal.',
                    'responsible_party' => 'Emissora',
                    'responsible_area'  => 'Gestão',
                    'recurrence'        => 'Mensal',
                    'due_rule'          => 'Ate o 10 dia util de cada mes',  // "10" ≠ "30" in excerpt
                    'due_date'          => null,
                    'priority'          => 'medium',
                    'required_evidence' => null,
                    'source_clause'     => 'Clausula 8.1',
                    'source_page'       => null,
                    'source_excerpt'    => $sourceExcerpt,
                    'confidence_score'  => 0.80,
                    'review_notes'      => null,
                ]]),
                200
            ),
        ]);

        config(['obligations.gemini.api_key' => 'fake-key-for-testing']);

        $document  = $this->makeDocument($chunkText);
        $extractor = app(GeminiObligationExtractor::class);
        $results   = $extractor->extract($document);

        // Must be skipped — "10" contradicts "30" in the source excerpt
        $this->assertSame([], $results);

        $stats   = $extractor->getExtractionStats();
        $reasons = array_column($stats['skipped_reasons'], 'reason');
        $this->assertContains('unsupported_due_rule', $reasons);
    }

    #[Test]
    public function gemini_due_rule_with_matching_number_is_accepted(): void
    {
        // source_excerpt and due_rule both reference "30" — consistent, must pass
        $sourceExcerpt = 'ate o 30 trigesimo dia apos o termino de cada mes relatorio mensal de acompanhamento';
        $chunkText     = str_repeat($sourceExcerpt . ' ', 15);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->fakeGeminiResponse([[
                    'title'             => 'Elaborar relatório mensal',
                    'obligation_type'   => 'Relatório',
                    'description'       => 'Elaborar relatório mensal.',
                    'responsible_party' => 'Emissora',
                    'responsible_area'  => 'Gestão',
                    'recurrence'        => 'Mensal',
                    'due_rule'          => 'ate o 30 dia apos o termino de cada mes',  // "30" matches
                    'due_date'          => null,
                    'priority'          => 'medium',
                    'required_evidence' => null,
                    'source_clause'     => 'Clausula 8.1',
                    'source_page'       => null,
                    'source_excerpt'    => $sourceExcerpt,
                    'confidence_score'  => 0.88,
                    'review_notes'      => null,
                ]]),
                200
            ),
        ]);

        config(['obligations.gemini.api_key' => 'fake-key-for-testing']);

        $document  = $this->makeDocument($chunkText);
        $extractor = app(GeminiObligationExtractor::class);
        $results   = $extractor->extract($document);

        $this->assertCount(1, $results);
        $this->assertSame(0.88, $results[0]['confidence_score']);
        $this->assertNull($results[0]['review_notes']);
    }

    #[Test]
    public function gemini_due_rule_without_numbers_is_accepted(): void
    {
        // due_rule with no numbers (e.g. "Sob demanda") — no numeric check, must pass
        $sourceExcerpt = 'A Emissora deve comunicar ao Agente Fiduciario qualquer Evento de Inadimplemento';
        $chunkText     = str_repeat($sourceExcerpt . ' ', 15);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->fakeGeminiResponse([[
                    'title'             => 'Comunicar Evento de Inadimplemento',
                    'obligation_type'   => 'Comunicação',
                    'description'       => 'Comunicar ao Agente Fiduciário.',
                    'responsible_party' => 'Emissora',
                    'responsible_area'  => 'Jurídico',
                    'recurrence'        => 'Sob demanda',
                    'due_rule'          => 'Imediatamente após o evento',  // no numbers
                    'due_date'          => null,
                    'priority'          => 'high',
                    'required_evidence' => null,
                    'source_clause'     => null,
                    'source_page'       => null,
                    'source_excerpt'    => $sourceExcerpt,
                    'confidence_score'  => 0.82,
                    'review_notes'      => null,
                ]]),
                200
            ),
        ]);

        config(['obligations.gemini.api_key' => 'fake-key-for-testing']);

        $document  = $this->makeDocument($chunkText);
        $extractor = app(GeminiObligationExtractor::class);
        $results   = $extractor->extract($document);

        $this->assertCount(1, $results);
        $this->assertSame('Comunicar Evento de Inadimplemento', $results[0]['title']);
    }

    #[Test]
    public function gemini_due_rule_with_dia_util_not_in_excerpt_is_skipped(): void
    {
        // due_rule mentions "Dia Útil" but source_excerpt says "dia após" (no "Dia Útil")
        // This is the exact cross-clause contamination: due_rule from clause 12 imported
        // into an obligation whose source_excerpt comes from clause 8.
        $sourceExcerpt = 'A Emissora elaborara relatorio mensal de acompanhamento da operacao';
        $chunkText     = str_repeat($sourceExcerpt . ' ', 15);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->fakeGeminiResponse([[
                    'title'             => 'Elaborar relatório mensal',
                    'obligation_type'   => 'Relatório',
                    'description'       => 'Elaborar relatório mensal.',
                    'responsible_party' => 'Emissora',
                    'responsible_area'  => 'Gestão',
                    'recurrence'        => 'Mensal',
                    // "Dia Útil" is NOT present in source_excerpt — hallucinated from another clause
                    'due_rule'          => 'Ate o Dia Util seguinte ao encerramento do mes',
                    'due_date'          => null,
                    'priority'          => 'medium',
                    'required_evidence' => null,
                    'source_clause'     => null,
                    'source_page'       => null,
                    'source_excerpt'    => $sourceExcerpt,
                    'confidence_score'  => 0.85,
                    'review_notes'      => null,
                ]]),
                200
            ),
        ]);

        config(['obligations.gemini.api_key' => 'fake-key-for-testing']);

        $document  = $this->makeDocument($chunkText);
        $extractor = app(GeminiObligationExtractor::class);
        $results   = $extractor->extract($document);

        // Must be skipped — "dia util" in due_rule but not in source_excerpt
        $this->assertSame([], $results);

        $stats   = $extractor->getExtractionStats();
        $reasons = array_column($stats['skipped_reasons'], 'reason');
        $this->assertContains('unsupported_due_rule', $reasons);
    }

    #[Test]
    public function null_source_clause_caps_confidence_at_70_percent(): void
    {
        $sourceExcerpt = 'A Emissora devera comunicar ao Agente Fiduciario qualquer alteracao relevante na operacao';
        $chunkText     = str_repeat($sourceExcerpt . ' ', 15);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->fakeGeminiResponse([[
                    'title'             => 'Comunicar alteração relevante',
                    'obligation_type'   => 'Comunicação',
                    'description'       => 'Comunicar ao Agente Fiduciário.',
                    'responsible_party' => 'Emissora',
                    'responsible_area'  => 'Jurídico',
                    'recurrence'        => 'Sob demanda',
                    'due_rule'          => null,
                    'due_date'          => null,
                    'priority'          => 'medium',
                    'required_evidence' => null,
                    'source_clause'     => null,   // no clause — confidence must be capped
                    'source_page'       => null,
                    'source_excerpt'    => $sourceExcerpt,
                    'confidence_score'  => 0.88,   // AI reports high, but no clause to verify
                    'review_notes'      => null,
                ]]),
                200
            ),
        ]);

        config(['obligations.gemini.api_key' => 'fake-key-for-testing']);

        $document  = $this->makeDocument($chunkText);
        $extractor = app(GeminiObligationExtractor::class);
        $results   = $extractor->extract($document);

        // Obligation passes validation but confidence must be capped at 0.70
        $this->assertCount(1, $results);
        $this->assertSame(0.70, $results[0]['confidence_score']);
        $this->assertNull($results[0]['source_clause']);
    }

    #[Test]
    public function null_source_clause_with_due_rule_caps_confidence_at_60_percent(): void
    {
        // The combination of (no clause + has a deadline) is the highest-risk scenario
        // for cross-clause contamination — cap at 0.60.
        $sourceExcerpt = 'A Emissora devera elaborar relatorio em ate 30 dias apos o encerramento do mes';
        $chunkText     = str_repeat($sourceExcerpt . ' ', 15);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->fakeGeminiResponse([[
                    'title'             => 'Elaborar relatório em 30 dias',
                    'obligation_type'   => 'Relatório',
                    'description'       => 'Elaborar relatório mensal.',
                    'responsible_party' => 'Emissora',
                    'responsible_area'  => 'Gestão',
                    'recurrence'        => 'Mensal',
                    'due_rule'          => 'ate 30 dias apos o encerramento do mes',  // "30" matches
                    'due_date'          => null,
                    'priority'          => 'medium',
                    'required_evidence' => null,
                    'source_clause'     => null,   // no clause → cap at 0.60 when due_rule present
                    'source_page'       => null,
                    'source_excerpt'    => $sourceExcerpt,
                    'confidence_score'  => 0.92,   // AI reports very high confidence
                    'review_notes'      => null,
                ]]),
                200
            ),
        ]);

        config(['obligations.gemini.api_key' => 'fake-key-for-testing']);

        $document  = $this->makeDocument($chunkText);
        $extractor = app(GeminiObligationExtractor::class);
        $results   = $extractor->extract($document);

        // Passes validation ("30" in both due_rule and source_excerpt), but confidence
        // must be capped at 0.60 because source_clause is null and due_rule is present.
        $this->assertCount(1, $results);
        $this->assertSame(0.60, $results[0]['confidence_score']);
        $this->assertNull($results[0]['source_clause']);
    }

    #[Test]
    public function gemini_deduplication_prefers_higher_confidence_candidate(): void
    {
        $clauseText = 'A Emissora deve enviar relatorio ao Agente Fiduciario mensalmente.';
        $chunkText  = str_repeat($clauseText . ' ', 20);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->fakeGeminiResponse([
                    [
                        'title'             => 'Enviar relatório ao Agente Fiduciário',
                        'obligation_type'   => 'Relatório',
                        'description'       => 'Enviar relatório mensal.',
                        'responsible_party' => 'Emissora',
                        'responsible_area'  => 'Gestão',
                        'recurrence'        => 'Mensal',
                        'due_rule'          => null,
                        'due_date'          => null,
                        'priority'          => 'medium',
                        'required_evidence' => null,
                        'source_clause'     => null,         // weaker: no clause
                        'source_page'       => null,
                        'source_excerpt'    => $clauseText,
                        'confidence_score'  => 0.65,         // lower confidence
                        'review_notes'      => null,
                    ],
                    [
                        'title'             => 'Enviar relatório ao Agente Fiduciário',
                        'obligation_type'   => 'Relatório',
                        'description'       => 'Enviar relatório mensal ao Agente Fiduciário.',
                        'responsible_party' => 'Emissora',
                        'responsible_area'  => 'Gestão',
                        'recurrence'        => 'Mensal',
                        'due_rule'          => null,
                        'due_date'          => null,
                        'priority'          => 'medium',
                        'required_evidence' => null,
                        'source_clause'     => 'Cláusula 8.1', // stronger: has clause
                        'source_page'       => null,
                        'source_excerpt'    => $clauseText,
                        'confidence_score'  => 0.88,            // higher confidence
                        'review_notes'      => null,
                    ],
                ]),
                200
            ),
        ]);

        config(['obligations.gemini.api_key' => 'fake-key-for-testing']);

        $document  = $this->makeDocument($chunkText);
        $extractor = app(GeminiObligationExtractor::class);

        $results = $extractor->extract($document);

        // Should keep only one — the higher-confidence one with source_clause
        $this->assertCount(1, $results);
        $this->assertSame(0.88, $results[0]['confidence_score']);
        $this->assertSame('Cláusula 8.1', $results[0]['source_clause']);
    }

    #[Test]
    public function extraction_service_stores_stats_in_metadata(): void
    {
        $clauseText = 'A Emissora elaborara relatorio mensal de acompanhamento da operacao conforme previsto.';
        $chunkText  = str_repeat($clauseText . ' ', 15);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->fakeGeminiResponse([[
                    'title'             => 'Elaborar relatório mensal',
                    'obligation_type'   => 'Relatório',
                    'description'       => 'Elaborar relatório mensal.',
                    'responsible_party' => 'Emissora',
                    'responsible_area'  => 'Gestão',
                    'recurrence'        => 'Mensal',
                    'due_rule'          => null,
                    'due_date'          => null,
                    'priority'          => 'medium',
                    'required_evidence' => null,
                    'source_clause'     => 'Cláusula 8.1',
                    'source_page'       => null,
                    'source_excerpt'    => $clauseText,
                    'confidence_score'  => 0.85,
                    'review_notes'      => null,
                ]]),
                200
            ),
        ]);

        config([
            'obligations.extractor'      => 'gemini',
            'obligations.gemini.api_key' => 'fake-key-for-testing',
        ]);

        $document = $this->makeDocument($chunkText);
        $service  = app(ObligationExtractionService::class);
        $service->extractAndSave($document);

        $document->refresh();
        $meta = $document->extraction_metadata;

        $this->assertSame('gemini', $document->extraction_provider);
        $this->assertArrayHasKey('total_chunks_available', $meta);
        $this->assertArrayHasKey('chunks_processed', $meta);
        $this->assertArrayHasKey('obligations_returned', $meta);
        $this->assertArrayHasKey('obligations_created', $meta);
        $this->assertArrayHasKey('obligations_skipped', $meta);
        $this->assertArrayHasKey('started_at', $meta);
        $this->assertArrayHasKey('finished_at', $meta);
    }

    // ── ObligationExtractionService ───────────────────────────────────────────

    #[Test]
    public function extraction_service_creates_suggested_obligations(): void
    {
        $document = $this->makeDocument('Relatório de inadimplência deve ser enviado mensalmente.');
        $service  = app(ObligationExtractionService::class);

        $count = $service->extractAndSave($document);

        $this->assertGreaterThanOrEqual(0, $count);
        $this->assertSame(
            $count,
            ExtractedObligation::where('term_document_id', $document->id)->count()
        );
    }

    #[Test]
    public function extraction_service_persists_provider_metadata(): void
    {
        $document = $this->makeDocument('Some obligation text.');
        $service  = app(ObligationExtractionService::class);

        $service->extractAndSave($document);

        $document->refresh();

        $this->assertNotNull($document->extraction_provider);
        $this->assertNotNull($document->extraction_model);
        $this->assertIsArray($document->extraction_metadata);
        $this->assertArrayHasKey('suggestions_generated', $document->extraction_metadata);
    }

    #[Test]
    public function extraction_service_never_creates_approved_obligations_directly(): void
    {
        $document = $this->makeDocument('Relatório mensal deve ser enviado.');
        $service  = app(ObligationExtractionService::class);

        $service->extractAndSave($document);

        $approvedCount = ExtractedObligation::where('term_document_id', $document->id)
            ->where('status', 'approved')
            ->count();

        $this->assertSame(0, $approvedCount);
    }

    #[Test]
    public function extraction_service_removes_old_suggestions_before_re_extraction(): void
    {
        $document = $this->makeDocument('Some text.');
        $service  = app(ObligationExtractionService::class);

        // First run
        $service->extractAndSave($document);
        $firstCount = ExtractedObligation::where('term_document_id', $document->id)->count();

        // Second run — old suggested ones should be removed and replaced
        $service->extractAndSave($document);
        $secondCount = ExtractedObligation::where('term_document_id', $document->id)->count();

        $this->assertSame($firstCount, $secondCount);
    }

    // ── Review flow ───────────────────────────────────────────────────────────

    #[Test]
    public function approving_extracted_obligation_creates_active_obligation(): void
    {
        $document  = $this->makeDocument('Some text.');
        $operation = $document->operation;

        $extracted = ExtractedObligation::create([
            'operation_id'     => $operation->id,
            'term_document_id' => $document->id,
            'title'            => 'Test Obligation',
            'obligation_type'  => 'Relatório',
            'description'      => 'Must send monthly report.',
            'priority'         => 'medium',
            'status'           => 'suggested',
        ]);

        // Simulate approve action
        $obligation = Obligation::create([
            'operation_id'            => $extracted->operation_id,
            'extracted_obligation_id' => $extracted->id,
            'title'                   => $extracted->title,
            'obligation_type'         => $extracted->obligation_type,
            'description'             => $extracted->description,
            'priority'                => $extracted->priority,
            'status'                  => 'on_track',
        ]);

        $extracted->update(['status' => 'approved']);

        $this->assertSame('approved', $extracted->fresh()->status);
        $this->assertSame('on_track', $obligation->status);
        $this->assertDatabaseHas('obligations', ['extracted_obligation_id' => $extracted->id]);
    }
}
