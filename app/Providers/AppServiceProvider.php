<?php

namespace App\Providers;

use App\Contracts\ObligationExtractorInterface;
use App\Services\GeminiObligationExtractor;
use App\Services\MockObligationExtractor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ObligationExtractorInterface::class, function ($app) {
            return match (config('obligations.extractor', 'mock')) {
                'gemini' => $app->make(GeminiObligationExtractor::class),
                default  => $app->make(MockObligationExtractor::class),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
