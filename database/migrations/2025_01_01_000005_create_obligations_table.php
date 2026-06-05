<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obligations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('extracted_obligation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('obligation_type');
            $table->text('description');
            $table->string('responsible_party')->nullable();
            $table->string('responsible_area')->nullable();
            $table->string('recurrence')->nullable();
            $table->string('due_rule')->nullable();
            $table->date('due_date')->nullable();
            $table->string('priority')->default('medium'); // low, medium, high, critical
            $table->string('status')->default('on_track'); // on_track, due_soon, overdue, completed, under_review
            $table->text('required_evidence')->nullable();
            $table->string('source_clause')->nullable();
            $table->unsignedSmallInteger('source_page')->nullable();
            $table->text('source_excerpt')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obligations');
    }
};
