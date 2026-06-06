<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obligation extends Model
{
    use HasFactory;

    protected $fillable = [
        'operation_id', 'extracted_obligation_id', 'title', 'obligation_type', 'obligation_category',
        'description', 'responsible_party', 'responsible_area', 'recurrence',
        'due_rule', 'due_date', 'priority', 'status', 'required_evidence',
        'source_clause', 'source_page', 'source_excerpt', 'notes',
    ];

    protected $casts = [
        'due_date'    => 'date',
        'source_page' => 'integer',
    ];

    public static function statusOptions(): array
    {
        return [
            'on_track'     => 'Em dia',
            'due_soon'     => 'A vencer',
            'overdue'      => 'Vencida',
            'completed'    => 'Concluída',
            'under_review' => 'Em análise',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            'low'      => 'Baixa',
            'medium'   => 'Média',
            'high'     => 'Alta',
            'critical' => 'Crítica',
        ];
    }

    public static function obligationTypes(): array
    {
        return [
            'Relatório Periódico',
            'Monitoramento de Carteira',
            'Controle de Fundo de Reserva',
            'Controle de Fundo de Obra',
            'Controle de Fundo de Despesas',
            'Demonstrações Financeiras',
            'Comprovação de Destinação de Recursos',
            'Monitoramento de Garantias',
            'Relatório de Medição de Obra',
            'Covenant Financeiro',
            'Covenant Operacional',
            'Comunicação a Investidores',
            'Monitoramento de Recebíveis',
            'Atualização Cadastral',
            'Acompanhamento do Patrimônio Separado',
            'Comunicação ao Agente Fiduciário',
            'Atualização de Quadro de Vendas',
            'Controle de Documentos Pendentes',
            'Evento de Vencimento Antecipado',
            'Evento de Inadimplemento',
            'Outro',
        ];
    }

    public function statusLabel(): string
    {
        return static::statusOptions()[$this->status] ?? $this->status;
    }

    public function priorityLabel(): string
    {
        return static::priorityOptions()[$this->priority] ?? $this->priority;
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    public function extractedObligation(): BelongsTo
    {
        return $this->belongsTo(ExtractedObligation::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ObligationHistory::class)->latest();
    }
}
