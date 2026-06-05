<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extracted_obligations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_document_chunk_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('obligation_type');
            $table->text('description');
            $table->string('responsible_party')->nullable();
            $table->string('responsible_area')->nullable();
            $table->string('recurrence')->nullable();
            $table->string('due_rule')->nullable();
            $table->date('due_date')->nullable();
            $table->string('priority')->default('medium'); // low, medium, high, critical
            $table->string('status')->default('suggested'); // suggested, approved, rejected, needs_review
            $table->text('required_evidence')->nullable();
            $table->string('source_clause')->nullable();
            $table->unsignedSmallInteger('source_page')->nullable();
            $table->text('source_excerpt')->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->text('review_notes')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracted_obligations');
    }
};
