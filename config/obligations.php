<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Obligation Extractor Provider
    |--------------------------------------------------------------------------
    | Supported: "mock", "gemini"
    |
    | "mock"   → keyword-based extraction (no external API, always works)
    | "gemini" → Google Gemini AI extraction (requires GEMINI_API_KEY)
    */
    'extractor' => env('OBLIGATION_EXTRACTOR', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Google Gemini Configuration
    |--------------------------------------------------------------------------
    |
    | After changing any value in .env you must clear the config cache and
    | restart the queue worker so the running process picks up the new values:
    |
    |   php artisan config:clear
    |   php artisan queue:restart
    */
    'gemini' => [
        'api_key'          => env('GEMINI_API_KEY'),
        'model'            => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'timeout'          => (int) env('GEMINI_API_TIMEOUT', 30),
        'max_chunk_chars'  => (int) env('GEMINI_MAX_CHUNK_CHARS', 8000),

        // null = no hard limit; positive integer = cap on chunk groups sent to Gemini.
        // Leave GEMINI_MAX_CHUNKS_PER_DOCUMENT empty or unset to process ALL chunks.
        'max_chunks_per_document' => (function () {
            $raw = env('GEMINI_MAX_CHUNKS_PER_DOCUMENT');
            if ($raw === null || (string) $raw === '') {
                return null;
            }
            $val = (int) $raw;
            return $val > 0 ? $val : null;
        })(),

        // "all"      → process chunk groups in document order, then apply max limit.
        // "relevant" → score each group by obligation-keyword density, sort highest
        //              first, then apply the max limit. Useful when max is small and
        //              you want the most obligation-dense sections of a large document.
        'chunk_selection_mode' => env('GEMINI_CHUNK_SELECTION_MODE', 'all'),

        'redact_sensitive' => (bool) env('GEMINI_REDACT_SENSITIVE_DATA', true),
        'base_url'         => 'https://generativelanguage.googleapis.com/v1beta/models',
    ],

];
