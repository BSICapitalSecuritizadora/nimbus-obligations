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
    */
    'gemini' => [
        'api_key'             => env('GEMINI_API_KEY'),
        'model'               => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'timeout'             => (int) env('GEMINI_API_TIMEOUT', 30),
        'max_chunk_chars'     => (int) env('GEMINI_MAX_CHUNK_CHARS', 8000),
        'max_chunks_per_document' => (int) env('GEMINI_MAX_CHUNKS_PER_DOCUMENT', 3),
        'redact_sensitive'    => (bool) env('GEMINI_REDACT_SENSITIVE_DATA', true),
        'base_url'            => 'https://generativelanguage.googleapis.com/v1beta/models',
    ],

];
