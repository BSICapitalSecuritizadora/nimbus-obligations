<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('page_number')->nullable();
            $table->string('section_title')->nullable();
            $table->string('clause_reference')->nullable();
            $table->longText('content');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_document_chunks');
    }
};
