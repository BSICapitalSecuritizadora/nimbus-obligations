<?php

namespace App\Services;

use App\Contracts\ObligationExtractorInterface;
use App\Models\TermDocument;

/**
 * Keyword-based mock extractor for development / demo mode.
 *
 * Analyses extracted text and produces realistic suggested obligations
 * WITHOUT calling any external AI API. Replace (or complement) with
 * AiObligationExtractor when AI integration is ready.
 */
class MockObligationExtractor implements ObligationExtractorInterface
{
    private ?string $lastError = null;

    public function getProviderName(): string { return 'mock'; }
    public function getModelName(): string    { return 'keyword-patterns'; }
    public function getLastError(): ?string   { return $this->lastError; }

    /**
     * Keyword → obligation template map.
     * Each entry: [ keywords[], template[] ]
     */
    private const PATTERNS = [
        [
            'keywords' => ['relatório mensal', 'relatório de acompanhamento', 'acompanhamento mensal'],
            'template' => [
                'title'              => 'Envio do Relatório Mensal de Acompanhamento',
                'obligation_type'    => 'Relatório Periódico',
                'description'        => 'Envio mensal do relatório de acompanhamento da operação ao Agente Fiduciário, contendo posição da carteira, inadimplência e indicadores contratuais.',
                'responsible_area'   => 'Estruturação',
                'responsible_party'  => 'Responsável pela Estruturação',
                'recurrence'         => 'Mensal',
                'due_rule'           => 'Até o 10º dia útil de cada mês',
                'priority'           => 'high',
                'required_evidence'  => 'Relatório assinado pelo Diretor Responsável com confirmação de recebimento pelo Agente Fiduciário.',
                'confidence_score'   => 0.88,
            ],
        ],
        [
            'keywords' => ['carteira de recebíveis', 'atualização da carteira', 'recebíveis elegíveis'],
            'template' => [
                'title'              => 'Atualização da Carteira de Recebíveis',
                'obligation_type'    => 'Monitoramento de Recebíveis',
                'description'        => 'Atualização mensal da carteira de recebíveis com validação dos contratos elegíveis e verificação do índice de inadimplência.',
                'responsible_area'   => 'Risco',
                'responsible_party'  => 'Área de Risco',
                'recurrence'         => 'Mensal',
                'due_rule'           => 'Até o último dia útil do mês de referência',
                'priority'           => 'critical',
                'required_evidence'  => 'Planilha de carteira atualizada e confirmação do Agente Fiduciário.',
                'confidence_score'   => 0.84,
            ],
        ],
        [
            'keywords' => ['fundo de reserva', 'reserva mínima', 'saldo mínimo do fundo'],
            'template' => [
                'title'              => 'Verificação do Fundo de Reserva',
                'obligation_type'    => 'Controle de Fundo de Reserva',
                'description'        => 'Verificação trimestral do saldo mínimo do Fundo de Reserva conforme percentual estabelecido no Termo de Securitização.',
                'responsible_area'   => 'Controladoria',
                'responsible_party'  => 'Controladoria',
                'recurrence'         => 'Trimestral',
                'due_rule'           => 'Último dia útil de cada trimestre',
                'priority'           => 'high',
                'required_evidence'  => 'Extrato bancário da conta vinculada ao Fundo de Reserva e laudo de conformidade.',
                'confidence_score'   => 0.82,
            ],
        ],
        [
            'keywords' => ['demonstrações financeiras', 'balanço', 'dre', 'resultado', 'auditoria externa'],
            'template' => [
                'title'              => 'Envio das Demonstrações Financeiras Auditadas',
                'obligation_type'    => 'Demonstrações Financeiras',
                'description'        => 'Envio das demonstrações financeiras anuais auditadas ao Agente Fiduciário.',
                'responsible_area'   => 'Jurídico',
                'responsible_party'  => 'Departamento Jurídico',
                'recurrence'         => 'Anual',
                'due_rule'           => 'Até 30 de abril do exercício seguinte',
                'priority'           => 'high',
                'required_evidence'  => 'Demonstrações assinadas pelo auditor independente e protocolo de entrega ao Agente Fiduciário.',
                'confidence_score'   => 0.85,
            ],
        ],
        [
            'keywords' => ['destinação dos recursos', 'aplicação dos recursos', 'comprovação de destinação'],
            'template' => [
                'title'              => 'Comprovação de Destinação dos Recursos',
                'obligation_type'    => 'Comprovação de Destinação de Recursos',
                'description'        => 'Comprovação trimestral da destinação dos recursos captados na emissão conforme prospecto.',
                'responsible_area'   => 'Estruturação',
                'responsible_party'  => 'Responsável pela Estruturação',
                'recurrence'         => 'Trimestral',
                'due_rule'           => 'Até 15 dias após o encerramento do trimestre',
                'priority'           => 'high',
                'required_evidence'  => 'Relatório de destinação assinado pela Diretoria com comprovantes bancários.',
                'confidence_score'   => 0.79,
            ],
        ],
        [
            'keywords' => ['garantia', 'avaliação de garantia', 'laudo de avaliação', 'imóvel dado em garantia'],
            'template' => [
                'title'              => 'Atualização da Avaliação de Garantias',
                'obligation_type'    => 'Monitoramento de Garantias',
                'description'        => 'Atualização semestral da avaliação das garantias reais vinculadas à operação com laudo de avaliador independente.',
                'responsible_area'   => 'Risco',
                'responsible_party'  => 'Área de Risco',
                'recurrence'         => 'Semestral',
                'due_rule'           => 'Último dia útil do semestre',
                'priority'           => 'medium',
                'required_evidence'  => 'Laudo de avaliação emitido por avaliador independente aprovado pela Securitizadora.',
                'confidence_score'   => 0.78,
            ],
        ],
        [
            'keywords' => ['medição de obra', 'cronograma físico', 'avanço de obra', 'construção'],
            'template' => [
                'title'              => 'Relatório de Medição de Obra',
                'obligation_type'    => 'Relatório de Medição de Obra',
                'description'        => 'Envio mensal do relatório de medição de obra elaborado pela construtora responsável.',
                'responsible_area'   => 'Engenharia',
                'responsible_party'  => 'Fiscal de Obras',
                'recurrence'         => 'Mensal',
                'due_rule'           => 'Até o 5º dia útil do mês subsequente',
                'priority'           => 'critical',
                'required_evidence'  => 'Relatório de medição assinado pela construtora e pelo fiscal da Securitizadora, com fotos.',
                'confidence_score'   => 0.86,
            ],
        ],
        [
            'keywords' => ['covenant', 'índice financeiro', 'dívida líquida', 'ebitda', 'cobertura de juros'],
            'template' => [
                'title'              => 'Verificação de Covenants Financeiros',
                'obligation_type'    => 'Covenant Financeiro',
                'description'        => 'Verificação trimestral do cumprimento dos covenants financeiros estabelecidos na escritura de emissão.',
                'responsible_area'   => 'Controladoria',
                'responsible_party'  => 'CFO / Controladoria',
                'recurrence'         => 'Trimestral',
                'due_rule'           => 'Até 45 dias após o encerramento do trimestre',
                'priority'           => 'critical',
                'required_evidence'  => 'Certificado de cumprimento assinado pelo CFO e auditor externo com demonstrativo dos índices.',
                'confidence_score'   => 0.90,
            ],
        ],
        [
            'keywords' => ['investidores', 'comunicação aos investidores', 'debenturistas', 'assembleia'],
            'template' => [
                'title'              => 'Relatório Trimestral aos Investidores',
                'obligation_type'    => 'Comunicação a Investidores',
                'description'        => 'Envio trimestral de relatório informativo aos investidores com posição da operação e perspectivas.',
                'responsible_area'   => 'Relações com Investidores',
                'responsible_party'  => 'RI',
                'recurrence'         => 'Trimestral',
                'due_rule'           => 'Até 15 dias após o encerramento do trimestre',
                'priority'           => 'medium',
                'required_evidence'  => 'Comprovante de envio a todos os investidores cadastrados com confirmação de recebimento.',
                'confidence_score'   => 0.74,
            ],
        ],
        [
            'keywords' => ['agente fiduciário', 'comunicação ao agente', 'evento material'],
            'template' => [
                'title'              => 'Comunicação ao Agente Fiduciário',
                'obligation_type'    => 'Comunicação ao Agente Fiduciário',
                'description'        => 'Comunicação imediata ao Agente Fiduciário sobre qualquer evento material que possa afetar o desempenho da operação.',
                'responsible_area'   => 'Jurídico',
                'responsible_party'  => 'Departamento Jurídico',
                'recurrence'         => 'Eventual',
                'due_rule'           => 'Imediatamente após o evento',
                'priority'           => 'medium',
                'required_evidence'  => 'Protocolo de comunicação com data/hora e confirmação de recebimento.',
                'confidence_score'   => 0.72,
            ],
        ],
        [
            'keywords' => ['patrimônio separado', 'segregação', 'integridade do patrimônio'],
            'template' => [
                'title'              => 'Monitoramento do Patrimônio Separado',
                'obligation_type'    => 'Acompanhamento do Patrimônio Separado',
                'description'        => 'Verificação mensal da segregação e integridade do patrimônio separado afeto à emissão.',
                'responsible_area'   => 'Controladoria',
                'responsible_party'  => 'Controladoria',
                'recurrence'         => 'Mensal',
                'due_rule'           => 'Último dia útil do mês',
                'priority'           => 'high',
                'required_evidence'  => 'Relatório de integridade do patrimônio separado assinado pelo Diretor de Operações.',
                'confidence_score'   => 0.80,
            ],
        ],
        [
            'keywords' => ['quadro de vendas', 'vendas da safra', 'comercialização', 'contratos de venda'],
            'template' => [
                'title'              => 'Atualização do Quadro de Vendas',
                'obligation_type'    => 'Atualização de Quadro de Vendas',
                'description'        => 'Envio mensal do quadro de vendas da safra com demonstrativo de contratos firmados e entregas realizadas.',
                'responsible_area'   => 'Comercial',
                'responsible_party'  => 'Diretor Comercial',
                'recurrence'         => 'Mensal',
                'due_rule'           => 'Até o 8º dia útil do mês',
                'priority'           => 'medium',
                'required_evidence'  => 'Planilha de quadro de vendas assinada pelo Diretor Comercial.',
                'confidence_score'   => 0.75,
            ],
        ],
        [
            'keywords' => ['licença', 'licença ambiental', 'aneel', 'ibama', 'regularidade'],
            'template' => [
                'title'              => 'Verificação de Covenants Operacionais — Licenças',
                'obligation_type'    => 'Covenant Operacional',
                'description'        => 'Verificação semestral da manutenção das licenças ambientais e operacionais dos projetos vinculados.',
                'responsible_area'   => 'Jurídico',
                'responsible_party'  => 'Departamento Jurídico',
                'recurrence'         => 'Semestral',
                'due_rule'           => 'Até 30 dias após o encerramento do semestre',
                'priority'           => 'high',
                'required_evidence'  => 'Certidão de regularidade das licenças junto aos órgãos competentes.',
                'confidence_score'   => 0.77,
            ],
        ],
        [
            'keywords' => ['fundo de obra', 'recursos para obra', 'cronograma financeiro'],
            'template' => [
                'title'              => 'Monitoramento do Fundo de Obra',
                'obligation_type'    => 'Controle de Fundo de Obra',
                'description'        => 'Monitoramento mensal do Fundo de Obra com verificação dos recursos disponíveis para o cronograma físico-financeiro.',
                'responsible_area'   => 'Engenharia',
                'responsible_party'  => 'Fiscal de Obras',
                'recurrence'         => 'Mensal',
                'due_rule'           => 'Até o 10º dia útil do mês',
                'priority'           => 'high',
                'required_evidence'  => 'Extrato do Fundo de Obra, cronograma atualizado e parecer do engenheiro fiscal.',
                'confidence_score'   => 0.83,
            ],
        ],
        [
            'keywords' => ['inadimplência', 'índice de inadimplência', 'atraso', 'default'],
            'template' => [
                'title'              => 'Monitoramento de Inadimplência da Carteira',
                'obligation_type'    => 'Monitoramento de Recebíveis',
                'description'        => 'Acompanhamento mensal da inadimplência da carteira de recebíveis com comparação aos limites contratuais.',
                'responsible_area'   => 'Risco',
                'responsible_party'  => 'Área de Risco',
                'recurrence'         => 'Mensal',
                'due_rule'           => 'Junto ao relatório mensal',
                'priority'           => 'high',
                'required_evidence'  => 'Relatório de inadimplência com posição de cada recebível e cálculo do índice agregado.',
                'confidence_score'   => 0.81,
            ],
        ],
    ];

    public function extract(TermDocument $document): array
    {
        $text    = strtolower($document->extracted_text ?? '');
        $results = [];
        $seen    = []; // deduplicate by obligation_type

        foreach (self::PATTERNS as $pattern) {
            foreach ($pattern['keywords'] as $keyword) {
                if (str_contains($text, $keyword)) {
                    $type = $pattern['template']['obligation_type'];

                    if (isset($seen[$type])) {
                        continue 2; // skip duplicate type
                    }

                    $seen[$type] = true;

                    // Find the excerpt containing the keyword
                    $pos     = strpos($text, $keyword);
                    $start   = max(0, $pos - 150);
                    $excerpt = substr($document->extracted_text ?? '', $start, 400);

                    $results[] = array_merge($pattern['template'], [
                        'source_excerpt' => trim($excerpt).'...',
                        'source_clause'  => $this->guessClause($text, $pos),
                        'source_page'    => null,
                        'due_date'       => null,
                    ]);

                    continue 2; // next pattern
                }
            }
        }

        // If no patterns matched but we have text, add generic obligations
        if (empty($results) && strlen($text) > 200) {
            $results = $this->genericObligations($document);
        }

        return $results;
    }

    private function guessClause(string $text, int $nearPos): ?string
    {
        $searchArea = substr($text, max(0, $nearPos - 500), 600);

        if (preg_match('/cl[aá]usula\s+([\d\.]+[ª°]?)/i', $searchArea, $m)) {
            return 'Cláusula '.$m[1];
        }

        if (preg_match('/art\.?\s*(\d+)/i', $searchArea, $m)) {
            return 'Art. '.$m[1];
        }

        return null;
    }

    private function genericObligations(TermDocument $document): array
    {
        $excerpt = substr($document->extracted_text ?? '', 0, 300).'...';

        return [
            [
                'title'             => 'Envio de Relatório Periódico',
                'obligation_type'   => 'Relatório Periódico',
                'description'       => 'Obrigação de envio de relatório periódico identificada no Termo de Securitização.',
                'responsible_area'  => 'Estruturação',
                'responsible_party' => 'A definir',
                'recurrence'        => 'Mensal',
                'due_rule'          => 'A definir',
                'priority'          => 'medium',
                'required_evidence' => 'Relatório assinado pelo responsável.',
                'source_clause'     => null,
                'source_page'       => null,
                'source_excerpt'    => $excerpt,
                'confidence_score'  => 0.45,
                'due_date'          => null,
            ],
            [
                'title'             => 'Monitoramento de Recebíveis',
                'obligation_type'   => 'Monitoramento de Recebíveis',
                'description'       => 'Monitoramento dos recebíveis vinculados à operação conforme Termo de Securitização.',
                'responsible_area'  => 'Risco',
                'responsible_party' => 'A definir',
                'recurrence'        => 'Mensal',
                'due_rule'          => 'A definir',
                'priority'          => 'high',
                'required_evidence' => 'Planilha de carteira atualizada.',
                'source_clause'     => null,
                'source_page'       => null,
                'source_excerpt'    => $excerpt,
                'confidence_score'  => 0.45,
                'due_date'          => null,
            ],
        ];
    }
}
