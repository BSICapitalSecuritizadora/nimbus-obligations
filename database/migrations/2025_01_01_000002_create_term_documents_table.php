<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type')->default('application/pdf');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_hash')->nullable();
            $table->string('processing_status')->default('pending'); // pending, processing, processed, failed
            $table->longText('extracted_text')->nullable();
            $table->text('extraction_error')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_documents');
    }
};
