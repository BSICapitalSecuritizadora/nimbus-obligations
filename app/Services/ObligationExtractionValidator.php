<?php

namespace App\Services;

/**
 * Validates that AI-extracted obligation fields are self-consistent and grounded
 * in the source text, preventing hallucinated combinations from reaching reviewers.
 *
 * Design:
 *  - Pure validator — no side effects, no logging, no DB access.
 *  - All public methods return a result array: ['valid' => bool, ...]
 *  - Caller decides whether to skip or flag the obligation.
 */
class ObligationExtractionValidator
{
    /**
     * Validates that due_rule is numerically and semantically consistent with source_excerpt.
     *
     * Two checks are performed in order:
     *
     * 1. NUMERIC — every integer in due_rule must appear as a whole number in source_excerpt.
     *    "10" in due_rule with only "30" in excerpt is a definitive cross-clause contamination.
     *
     * 2. TEMPORAL KEYWORD — if due_rule mentions "dia útil" or "dias úteis", those exact
     *    terms must appear in source_excerpt.  "Até o 10º Dia Útil" cannot be supported by
     *    a source_excerpt that says "30º dia após o término" — the deadline type is different.
     *
     * Returns:
     *   ['valid' => true]
     *   ['valid' => false, 'reason' => 'unsupported_due_rule', 'detail' => '...']
     */
    public function validateDueRule(?string $dueRule, string $sourceExcerpt): array
    {
        if ($dueRule === null || trim($dueRule) === '') {
            return ['valid' => true]; // null is always acceptable
        }

        $normDueRule = $this->normalize($dueRule);
        $normExcerpt = $this->normalize($sourceExcerpt);

        // ── Check 1: Numeric ───────────────────────────────────────────────────
        // Every integer in due_rule must appear as a whole number in source_excerpt.
        // "10" in due_rule with "30" in excerpt is a definitive conflict.
        $dueNumbers = $this->extractIntegers($normDueRule);

        if (! empty($dueNumbers)) {
            $excerptNumbers = $this->extractIntegers($normExcerpt);

            foreach ($dueNumbers as $num) {
                if (! in_array($num, $excerptNumbers, true)) {
                    return [
                        'valid'  => false,
                        'reason' => 'unsupported_due_rule',
                        'detail' => sprintf(
                            'O prazo "%s" contém o número "%s" que não aparece no trecho de origem. ' .
                            'Números encontrados no trecho: [%s].',
                            $dueRule,
                            $num,
                            implode(', ', $excerptNumbers ?: ['nenhum'])
                        ),
                    ];
                }
            }
        }

        // ── Check 2: Temporal keyword "dia útil" / "dias úteis" ───────────────
        // If due_rule specifies a business-day deadline, source_excerpt must also
        // mention business days.  "Dia Útil" from clause 12 cannot be imported
        // into an obligation whose source_excerpt comes from clause 8.
        $diaUtilForms = ['dia util', 'dias uteis'];

        $dueRuleHasDiaUtil = false;
        foreach ($diaUtilForms as $form) {
            if (str_contains($normDueRule, $form)) {
                $dueRuleHasDiaUtil = true;
                break;
            }
        }

        if ($dueRuleHasDiaUtil) {
            $excerptHasDiaUtil = false;
            foreach ($diaUtilForms as $form) {
                if (str_contains($normExcerpt, $form)) {
                    $excerptHasDiaUtil = true;
                    break;
                }
            }

            if (! $excerptHasDiaUtil) {
                return [
                    'valid'  => false,
                    'reason' => 'unsupported_due_rule',
                    'detail' => sprintf(
                        'O prazo "%s" menciona "dia útil/dias úteis", mas o trecho de origem não contém ' .
                        'esse marcador temporal. Prazos em dias úteis não podem ser inferidos de trechos ' .
                        'que não os mencionam explicitamente.',
                        $dueRule
                    ),
                ];
            }
        }

        return ['valid' => true];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Normalises text: lowercase, strip diacritics, collapse whitespace.
     * Keeps digits and letters; removes punctuation.
     */
    public function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        // Strip combining diacritical marks (requires ext-intl, graceful fallback)
        if (class_exists('\Normalizer')) {
            $nfd = \Normalizer::normalize($text, \Normalizer::FORM_D);
            if ($nfd !== false) {
                $text = preg_replace('/\p{Mn}/u', '', $nfd) ?? $text;
            }
        }

        // Replace punctuation with spaces; keep alphanumerics
        $text = preg_replace('/[^\w\s]/u', ' ', $text) ?? $text;

        // Collapse whitespace
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Extracts all distinct integers from normalised text as strings.
     * Matches whole-word numbers only, so "30" does not match inside "300".
     *
     * Examples:
     *   "ate o 30 dia apos" → ["30"]
     *   "10 ou 30 dias"     → ["10", "30"]
     *   "trigesimo dia"     → []  (ordinal words excluded — too ambiguous)
     */
    public function extractIntegers(string $normalizedText): array
    {
        preg_match_all('/\b(\d+)\b/', $normalizedText, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }
}
