<?php

namespace App\Services;

/**
 * Masks personally identifiable and financially sensitive data from text
 * before it is sent to an external AI API.
 *
 * The masking preserves clause references, legal terminology and document
 * structure while removing data that could identify individuals or expose
 * confidential financial details.
 */
class SensitiveDataRedactor
{
    private const REPLACEMENTS = [
        // CPF — 000.000.000-00 or 00000000000
        '/\b\d{3}[\.\s]?\d{3}[\.\s]?\d{3}[-\s]?\d{2}\b/' => '[CPF]',

        // CNPJ — 00.000.000/0000-00 or 00000000000000
        '/\b\d{2}[\.\s]?\d{3}[\.\s]?\d{3}[\/\s]?\d{4}[-\s]?\d{2}\b/' => '[CNPJ]',

        // Brazilian phone numbers — (00) 00000-0000, +55 11 99999-9999, etc.
        '/(?:\+55\s?)?(?:\(?\d{2}\)?\s?)(?:9\s?)?\d{4,5}[-\s]?\d{4}\b/' => '[TELEFONE]',

        // Email addresses
        '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/' => '[EMAIL]',

        // PIX random key (UUID-like)
        '/\b[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\b/' => '[CHAVE-PIX]',

        // Bank account — "conta nº 12345-6" or "conta corrente 0001 12345-6"
        '/\b(?:conta\s+(?:corrente|poupan[çc]a|n[uú]mero|n[oº°]\.?)?\s*:?\s*)(\d{4,10}[-–]?\d{0,2})\b/i' => 'conta [CONTA]',

        // Bank agency/branch — "agência 1234" or "ag. 0001"
        '/\b(?:ag[eê]ncia|ag\.)\s*:?\s*(\d{4}(?:-\d{1,2})?)\b/i' => 'agência [AGÊNCIA]',
    ];

    public function redact(string $text): string
    {
        foreach (self::REPLACEMENTS as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return $text;
    }
}
