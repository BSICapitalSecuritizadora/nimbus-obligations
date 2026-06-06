<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FORWARD_MAP = [
        'on_track'     => 'em_dia',
        'due_soon'     => 'a_vencer',
        'overdue'      => 'vencida',
        'completed'    => 'concluida',
        'under_review' => 'em_analise',
    ];

    public function up(): void
    {
        foreach (self::FORWARD_MAP as $old => $new) {
            DB::table('obligations')->where('status', $old)->update(['status' => $new]);
        }
    }

    public function down(): void
    {
        foreach (array_flip(self::FORWARD_MAP) as $new => $old) {
            DB::table('obligations')->where('status', $new)->update(['status' => $old]);
        }
        // waiver, nao_aplicavel, pendente_evidencia have no legacy equivalent
    }
};
