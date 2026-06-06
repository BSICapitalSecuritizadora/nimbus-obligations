<?php

namespace App\Services;

use App\Models\Obligation;

class NonComplianceRiskService
{
    private const RISK_OPTIONS = [
        'baixo'  => 'Baixo',
        'medio'  => 'Médio',
        'alto'   => 'Alto',
        'critico' => 'Crítico',
    ];

    private const HIGH_RISK_CATEGORIES = [
        'Covenants',
        'Fundos',
        'Garantias',
        'Recebíveis / Lastro',
        'Regulatória',
    ];

    private const CONSEQUENCE_BY_CATEGORY = [
        'Informacional'         => 'Pode comprometer a transparência e o acompanhamento da operação.',
        'Covenants'             => 'Pode caracterizar descumprimento de covenant e demandar análise contratual.',
        'Fundos'                => 'Pode impactar a suficiência de fundos vinculados à operação.',
        'Garantias'             => 'Pode impactar o monitoramento ou a suficiência das garantias.',
        'Recebíveis / Lastro'   => 'Pode impactar o acompanhamento do lastro e dos recebíveis vinculados à operação.',
        'Obras'                 => 'Pode prejudicar o acompanhamento da evolução física e financeira da obra.',
        'Condições Precedentes' => 'Pode impedir ou restringir a continuidade de etapa relevante da operação.',
        'Assembleia / Waiver'   => 'Pode demandar deliberação, aprovação ou waiver pelos participantes da operação.',
        'Vencimento Antecipado' => 'Pode caracterizar evento de vencimento antecipado, conforme previsto nos documentos da operação.',
        'Patrimônio Separado'   => 'Pode impactar o acompanhamento e a segregação do patrimônio separado.',
        'Regulatória'           => 'Pode gerar pendência regulatória ou obrigação de comunicação a órgão competente.',
        'Financeira / Pagamento' => 'Pode gerar inadimplemento financeiro ou pendência de pagamento.',
        'Outro'                 => 'Pode gerar pendência operacional ou contratual a ser analisada.',
    ];

    /**
     * Suggests a non-compliance risk level based on priority and category.
     */
    public function suggestRisk(Obligation $obligation): string
    {
        if (
            $obligation->priority === 'critical' ||
            $obligation->obligation_category === 'Vencimento Antecipado'
        ) {
            return 'critico';
        }

        if (
            $obligation->priority === 'high' ||
            in_array($obligation->obligation_category, self::HIGH_RISK_CATEGORIES, true)
        ) {
            return 'alto';
        }

        if ($obligation->priority === 'medium') {
            return 'medio';
        }

        return 'baixo';
    }

    /**
     * Suggests a consequence description based on obligation category.
     */
    public function suggestConsequence(Obligation $obligation): string
    {
        $category = $obligation->obligation_category;
        return self::CONSEQUENCE_BY_CATEGORY[$category] ?? self::CONSEQUENCE_BY_CATEGORY['Outro'];
    }

    public static function getRiskOptions(): array
    {
        return self::RISK_OPTIONS;
    }

    public static function getRiskLabel(string $risk): string
    {
        return self::RISK_OPTIONS[$risk] ?? $risk;
    }

    public static function getRiskColor(string $risk): string
    {
        return match ($risk) {
            'critico' => 'danger',
            'alto'    => 'warning',
            'medio'   => 'info',
            'baixo'   => 'success',
            default   => 'gray',
        };
    }

    public static function isValid(?string $risk): bool
    {
        return $risk !== null && array_key_exists($risk, self::RISK_OPTIONS);
    }
}
