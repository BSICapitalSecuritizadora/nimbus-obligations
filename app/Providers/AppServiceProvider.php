<?php

namespace App\Providers;

use App\Contracts\ObligationExtractorInterface;
use App\Services\MockObligationExtractor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the extractor interface to the mock implementation.
        // To use a real AI extractor, swap MockObligationExtractor with
        // AiObligationExtractor here (or use a conditional based on env var).
        $this->app->bind(ObligationExtractorInterface::class, MockObligationExtractor::class);
    }

    public function boot(): void
    {
        //
    }
}
