<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obligations', function (Blueprint $table) {
            $table->string('non_compliance_risk', 20)->nullable()->after('status');
            $table->text('non_compliance_consequence')->nullable()->after('non_compliance_risk');
        });
    }

    public function down(): void
    {
        Schema::table('obligations', function (Blueprint $table) {
            $table->dropColumn(['non_compliance_risk', 'non_compliance_consequence']);
        });
    }
};
