<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('CRI'); // CRI, CRA, Debenture, Nota Comercial, CCB, Other
            $table->string('series')->nullable();
            $table->string('if_code')->nullable()->comment('Código IF na CETIP/B3');
            $table->string('issuer')->nullable()->comment('Emissora / Securitizadora');
            $table->string('debtor')->nullable()->comment('Devedor');
            $table->string('assignor')->nullable()->comment('Cedente');
            $table->string('fiduciary_agent')->nullable()->comment('Agente Fiduciário');
            $table->date('issue_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->string('status')->default('active'); // active, draft, closed
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
