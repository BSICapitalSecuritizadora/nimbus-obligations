<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extracted_obligations', function (Blueprint $table) {
            $table->string('obligation_category', 100)->nullable()->after('obligation_type');
        });

        Schema::table('obligations', function (Blueprint $table) {
            $table->string('obligation_category', 100)->nullable()->after('obligation_type');
        });
    }

    public function down(): void
    {
        Schema::table('extracted_obligations', function (Blueprint $table) {
            $table->dropColumn('obligation_category');
        });

        Schema::table('obligations', function (Blueprint $table) {
            $table->dropColumn('obligation_category');
        });
    }
};
