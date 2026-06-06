<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extracted_obligations', function (Blueprint $table) {
            $table->string('ai_provider')->nullable()->after('reviewed_at');
            $table->string('ai_model')->nullable()->after('ai_provider');
        });
    }

    public function down(): void
    {
        Schema::table('extracted_obligations', function (Blueprint $table) {
            $table->dropColumn(['ai_provider', 'ai_model']);
        });
    }
};
