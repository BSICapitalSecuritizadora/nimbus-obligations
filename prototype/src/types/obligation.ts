export type ObligationStatus =
  | 'Em dia'
  | 'A vencer'
  | 'Vencida'
  | 'Concluída'
  | 'Em análise';

export type ObligationPriority = 'Baixa' | 'Média' | 'Alta' | 'Crítica';

export interface ObligationHistoryItem {
  date: string;
  user: string;
  action: string;
}

export interface EmissionObligation {
  id: string;
  emission: string;
  series?: string;
  obligationType: string;
  description: string;
  responsibleArea: string;
  responsiblePerson: string;
  dueDate: string;
  recurrence: string;
  status: ObligationStatus;
  priority: ObligationPriority;
  termReference: string;
  requiredEvidence: string;
  lastUpdate: string;
  notes?: string;
  history: ObligationHistoryItem[];
}

export interface ObligationFilters {
  emission: string;
  status: string;
  obligationType: string;
  responsibleArea: string;
  priority: string;
  dueDateFrom: string;
  dueDateTo: string;
}
