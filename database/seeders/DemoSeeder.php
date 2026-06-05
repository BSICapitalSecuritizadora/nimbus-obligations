<?php

namespace Database\Seeders;

use App\Models\Obligation;
use App\Models\ObligationHistory;
use App\Models\Operation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin user ────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@nimbus.local'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );

        // ── Operations ────────────────────────────────────────────────────────
        $ops = [
            [
                'name'           => 'CRI Residencial Aurora 2025',
                'type'           => 'CRI',
                'series'         => '1ª e 2ª Série',
                'if_code'        => 'CRI25A0001',
                'issuer'         => 'Aurora Securitizadora S.A.',
                'debtor'         => 'Construtora Aurora Ltda.',
                'assignor'       => 'Construtora Aurora Ltda.',
                'fiduciary_agent'=> 'Oliveira Trust DTVM',
                'issue_date'     => '2025-03-15',
                'maturity_date'  => '2035-03-15',
                'status'         => 'active',
            ],
            [
                'name'           => 'CRA Agro Safra Forte 2025',
                'type'           => 'CRA',
                'series'         => '1ª e 2ª Série',
                'if_code'        => 'CRA25S0002',
                'issuer'         => 'Safra Forte Securitizadora S.A.',
                'debtor'         => 'Grãos do Cerrado S.A.',
                'assignor'       => 'Grãos do Cerrado S.A.',
                'fiduciary_agent'=> 'Vórtx DTVM',
                'issue_date'     => '2025-03-20',
                'maturity_date'  => '2028-03-20',
                'status'         => 'active',
            ],
            [
                'name'           => 'CRI Prime Offices 2024',
                'type'           => 'CRI',
                'series'         => '1ª e 2ª Série',
                'if_code'        => 'CRI24P0003',
                'issuer'         => 'Prime Securitizadora S.A.',
                'debtor'         => 'Prime Offices FII',
                'assignor'       => 'Prime Offices FII',
                'fiduciary_agent'=> 'Pentágono DTVM',
                'issue_date'     => '2024-06-01',
                'maturity_date'  => '2032-06-01',
                'status'         => 'active',
            ],
            [
                'name'           => 'Debêntures Infra Energia 2025',
                'type'           => 'Debenture',
                'series'         => '1ª e 2ª Série',
                'if_code'        => 'DEB25E0004',
                'issuer'         => 'Energia Renovável S.A.',
                'debtor'         => 'Energia Renovável S.A.',
                'assignor'       => null,
                'fiduciary_agent'=> 'Correnteza Fiduciária',
                'issue_date'     => '2025-01-15',
                'maturity_date'  => '2040-01-15',
                'status'         => 'active',
            ],
        ];

        $createdOps = [];
        foreach ($ops as $opData) {
            $createdOps[] = Operation::firstOrCreate(['if_code' => $opData['if_code']], $opData);
        }

        // ── Obligations ───────────────────────────────────────────────────────
        $obligations = [
            // CRI Residencial Aurora 2025
            [
                'operation_id'    => $createdOps[0]->id,
                'title'           => 'Envio Mensal do Relatório de Acompanhamento',
                'obligation_type' => 'Relatório Periódico',
                'description'     => 'Envio mensal do relatório de acompanhamento da operação ao Agente Fiduciário, contendo posição da carteira, inadimplência e indicadores contratuais.',
                'responsible_area'=> 'Estruturação',
                'responsible_party'=> 'Mariana Costa',
                'recurrence'      => 'Mensal',
                'due_rule'        => 'Até o 10º dia útil de cada mês',
                'due_date'        => now()->addDays(5)->format('Y-m-d'),
                'priority'        => 'high',
                'status'          => 'due_soon',
                'source_clause'   => 'Cláusula 8.1.2',
                'required_evidence'=> 'Relatório assinado pelo Diretor Responsável com confirmação de recebimento.',
            ],
            [
                'operation_id'    => $createdOps[0]->id,
                'title'           => 'Atualização da Carteira de Recebíveis',
                'obligation_type' => 'Monitoramento de Recebíveis',
                'description'     => 'Atualização mensal da carteira de recebíveis imobiliários com validação dos contratos elegíveis e verificação de inadimplência.',
                'responsible_area'=> 'Risco',
                'responsible_party'=> 'Rafael Almeida',
                'recurrence'      => 'Mensal',
                'due_rule'        => 'Último dia útil do mês',
                'due_date'        => now()->subDays(3)->format('Y-m-d'),
                'priority'        => 'critical',
                'status'          => 'overdue',
                'source_clause'   => 'Cláusula 9.3.1',
                'required_evidence'=> 'Planilha de carteira atualizada e confirmação do Agente Fiduciário.',
            ],
            // CRA Agro Safra Forte 2025
            [
                'operation_id'    => $createdOps[1]->id,
                'title'           => 'Verificação do Fundo de Reserva',
                'obligation_type' => 'Controle de Fundo de Reserva',
                'description'     => 'Verificação trimestral do saldo mínimo do Fundo de Reserva conforme percentual do Termo de Securitização.',
                'responsible_area'=> 'Controladoria',
                'responsible_party'=> 'Fernanda Lima',
                'recurrence'      => 'Trimestral',
                'due_rule'        => 'Último dia útil do trimestre',
                'due_date'        => now()->addDays(25)->format('Y-m-d'),
                'priority'        => 'high',
                'status'          => 'on_track',
                'source_clause'   => 'Cláusula 12.4',
                'required_evidence'=> 'Extrato bancário e laudo de conformidade.',
            ],
            [
                'operation_id'    => $createdOps[1]->id,
                'title'           => 'Envio das Demonstrações Financeiras Auditadas',
                'obligation_type' => 'Demonstrações Financeiras',
                'description'     => 'Envio das demonstrações financeiras anuais auditadas ao Agente Fiduciário.',
                'responsible_area'=> 'Jurídico',
                'responsible_party'=> 'Bruno Tavares',
                'recurrence'      => 'Anual',
                'due_rule'        => 'Até 30 de abril do exercício seguinte',
                'due_date'        => now()->subDays(46)->format('Y-m-d'),
                'priority'        => 'high',
                'status'          => 'completed',
                'source_clause'   => 'Cláusula 7.2',
                'required_evidence'=> 'Demonstrações assinadas pelo auditor e protocolo de entrega.',
            ],
            // CRI Prime Offices 2024
            [
                'operation_id'    => $createdOps[2]->id,
                'title'           => 'Relatório de Medição de Obra — Junho',
                'obligation_type' => 'Relatório de Medição de Obra',
                'description'     => 'Envio mensal do relatório de medição de obra do empreendimento imobiliário vinculado à emissão.',
                'responsible_area'=> 'Engenharia',
                'responsible_party'=> 'Lucas Ferreira',
                'recurrence'      => 'Mensal',
                'due_rule'        => 'Até o 5º dia útil do mês',
                'due_date'        => now()->subDays(1)->format('Y-m-d'),
                'priority'        => 'critical',
                'status'          => 'overdue',
                'source_clause'   => 'Cláusula 10.1.3',
                'required_evidence'=> 'Relatório assinado pela construtora e fiscal, com fotos.',
            ],
            [
                'operation_id'    => $createdOps[2]->id,
                'title'           => 'Monitoramento do Fundo de Obra',
                'obligation_type' => 'Controle de Fundo de Obra',
                'description'     => 'Monitoramento mensal do Fundo de Obra com verificação dos recursos para o cronograma financeiro.',
                'responsible_area'=> 'Engenharia',
                'responsible_party'=> 'Lucas Ferreira',
                'recurrence'      => 'Mensal',
                'due_rule'        => 'Até o 10º dia útil do mês',
                'due_date'        => now()->addDays(8)->format('Y-m-d'),
                'priority'        => 'high',
                'status'          => 'under_review',
                'source_clause'   => 'Cláusula 12.7',
                'required_evidence'=> 'Extrato do Fundo de Obra e parecer do engenheiro fiscal.',
            ],
            // Debêntures Infra Energia 2025
            [
                'operation_id'    => $createdOps[3]->id,
                'title'           => 'Verificação de Covenants Financeiros — 2T26',
                'obligation_type' => 'Covenant Financeiro',
                'description'     => 'Verificação trimestral do cumprimento dos covenants financeiros: Dívida Líquida/EBITDA ≤ 3,5x e Cobertura de Juros ≥ 2,0x.',
                'responsible_area'=> 'Controladoria',
                'responsible_party'=> 'Fernanda Lima',
                'recurrence'      => 'Trimestral',
                'due_rule'        => 'Até 45 dias após o encerramento do trimestre',
                'due_date'        => now()->addDays(25)->format('Y-m-d'),
                'priority'        => 'critical',
                'status'          => 'under_review',
                'source_clause'   => 'Cláusula 13.1 e 13.2',
                'required_evidence'=> 'Certificado de cumprimento assinado pelo CFO e auditor externo.',
                'notes'           => 'Índice Dívida/EBITDA aproximando do limite. Monitoramento intensificado.',
            ],
            [
                'operation_id'    => $createdOps[3]->id,
                'title'           => 'Relatório Trimestral aos Debenturistas',
                'obligation_type' => 'Comunicação a Investidores',
                'description'     => 'Envio trimestral de relatório informativo aos debenturistas com posição da operação e perspectivas.',
                'responsible_area'=> 'Relações com Investidores',
                'responsible_party'=> 'Camila Santos',
                'recurrence'      => 'Trimestral',
                'due_rule'        => 'Até 15 dias após o encerramento do trimestre',
                'due_date'        => now()->addDays(10)->format('Y-m-d'),
                'priority'        => 'medium',
                'status'          => 'due_soon',
                'source_clause'   => 'Cláusula 14.3',
                'required_evidence'=> 'Comprovante de envio a todos os debenturistas cadastrados.',
            ],
            [
                'operation_id'    => $createdOps[3]->id,
                'title'           => 'Monitoramento do Patrimônio Separado',
                'obligation_type' => 'Acompanhamento do Patrimônio Separado',
                'description'     => 'Verificação mensal da segregação e integridade do patrimônio separado afeto à emissão.',
                'responsible_area'=> 'Controladoria',
                'responsible_party'=> 'Fernanda Lima',
                'recurrence'      => 'Mensal',
                'due_rule'        => 'Último dia útil do mês',
                'due_date'        => now()->addDays(25)->format('Y-m-d'),
                'priority'        => 'high',
                'status'          => 'on_track',
                'source_clause'   => 'Cláusula 4.1',
                'required_evidence'=> 'Relatório de integridade do patrimônio separado.',
            ],
        ];

        foreach ($obligations as $oblData) {
            $obl = Obligation::create($oblData);

            ObligationHistory::create([
                'obligation_id' => $obl->id,
                'action'        => 'Obrigação criada pelo seeder de demonstração.',
                'new_value'     => $obl->status,
            ]);
        }
    }
}
