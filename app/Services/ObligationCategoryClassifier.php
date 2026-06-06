<?php

namespace App\Services;

class ObligationCategoryClassifier
{
    public const CATEGORIES = [
        'Informacional',
        'Covenants',
        'Fundos',
        'Garantias',
        'Recebíveis / Lastro',
        'Obras',
        'Condições Precedentes',
        'Assembleia / Waiver',
        'Vencimento Antecipado',
        'Patrimônio Separado',
        'Regulatória',
        'Financeira / Pagamento',
        'Outro',
    ];

    // Ordered most-specific first to avoid shadowing
    private static array $rules = [
        'Fundos' => [
            'fundo de reserva', 'fundo de obra', 'fundo de despesa', 'fundo de juros',
            'fundo de', 'reserva de liquidez', 'fundo operacional',
        ],
        'Covenants' => [
            'covenant', 'indice financeiro', 'índice financeiro',
            'indice de cobertura', 'índice de cobertura',
            'indice de endividamento', 'índice de endividamento',
        ],
        'Recebíveis / Lastro' => [
            'recebiveis', 'recebíveis', 'lastro',
            'creditos imobiliarios', 'créditos imobiliários',
            'carteira de recebiveis', 'carteira de recebíveis',
        ],
        'Obras' => [
            'medicao de obra', 'medição de obra',
            'relatorio de obra', 'relatório de obra',
            'cronograma fisico', 'cronograma físico',
            'evolucao de obra', 'evolução de obra',
            'relatorio de medicao', 'relatório de medição',
            'avanco de obra', 'avanço de obra',
        ],
        'Condições Precedentes' => [
            'condicao precedente', 'condição precedente',
            'condicoes precedentes', 'condições precedentes',
        ],
        'Assembleia / Waiver' => [
            'assembleia', 'waiver', 'renuncia', 'renúncia',
            'aprovacao de credores', 'aprovação de credores',
        ],
        'Vencimento Antecipado' => [
            'vencimento antecipado', 'evento de vencimento',
            'inadimplemento', 'inadimplencia', 'inadimplência',
            'evento de inadimplemento',
        ],
        'Patrimônio Separado' => [
            'patrimonio separado', 'patrimônio separado',
            'regime fiduciario', 'regime fiduciário',
            'acompanhamento do patrimonio', 'acompanhamento do patrimônio',
        ],
        'Garantias' => [
            'garantia', 'alienacao fiduciaria', 'alienação fiduciária',
            'hipoteca', 'penhor', 'seguro de', 'aval',
        ],
        'Regulatória' => [
            'cvm', 'comissao de valores', 'comissão de valores',
            'b3', 'cetip', 'anbima', 'receita federal',
            'regulatorio', 'regulatório', 'autoridade reguladora',
        ],
        'Informacional' => [
            'relatorio', 'relatório', 'demonstracoes financeiras', 'demonstrações financeiras',
            'comunicacao', 'comunicação', 'informacao', 'informação',
            'prestacao de contas', 'prestação de contas',
            'atualizacao cadastral', 'atualização cadastral',
        ],
        'Financeira / Pagamento' => [
            'pagamento', 'amortizacao', 'amortização', 'juros', 'tributo',
            'imposto', 'remuneracao', 'remuneração', 'repasse', 'desembolso',
        ],
    ];

    public static function classifyFromTypeAndText(
        ?string $type,
        ?string $title = null,
        ?string $description = null,
        ?string $sourceExcerpt = null
    ): string {
        $haystack = mb_strtolower(
            implode(' ', array_filter([$type, $title, $description, $sourceExcerpt])),
            'UTF-8'
        );

        foreach (static::$rules as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    return $category;
                }
            }
        }

        return 'Outro';
    }

    public static function isValid(?string $category): bool
    {
        return $category !== null && in_array($category, static::CATEGORIES, true);
    }

    public static function sanitize(?string $category): string
    {
        return static::isValid($category) ? (string) $category : 'Outro';
    }

    public static function categoryOptions(): array
    {
        return array_combine(static::CATEGORIES, static::CATEGORIES);
    }

    public static function categoryColor(string $category): string
    {
        return match ($category) {
            'Informacional'          => 'info',
            'Covenants'              => 'primary',
            'Fundos'                 => 'warning',
            'Garantias'              => 'success',
            'Recebíveis / Lastro'    => 'info',
            'Obras'                  => 'warning',
            'Condições Precedentes'  => 'gray',
            'Assembleia / Waiver'    => 'warning',
            'Vencimento Antecipado'  => 'danger',
            'Patrimônio Separado'    => 'primary',
            'Regulatória'            => 'info',
            'Financeira / Pagamento' => 'success',
            default                  => 'gray',
        };
    }
}
