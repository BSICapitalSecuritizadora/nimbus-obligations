<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('term_documents', function (Blueprint $table) {
            $table->string('extraction_provider')->nullable()->after('processed_at');
            $table->string('extraction_model')->nullable()->after('extraction_provider');
            $table->json('extraction_metadata')->nullable()->after('extraction_model');
        });
    }

    public function down(): void
    {
        Schema::table('term_documents', function (Blueprint $table) {
            $table->dropColumn(['extraction_provider', 'extraction_model', 'extraction_metadata']);
        });
    }
};
