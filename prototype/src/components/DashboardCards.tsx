import { EmissionObligation, ObligationStatus } from '../types/obligation';

interface Props {
  obligations: EmissionObligation[];
}

interface CardDef {
  label: string;
  status?: ObligationStatus;
  special?: 'total' | 'critica';
  colorClass: string;
  borderClass: string;
  textClass: string;
}

const CARDS: CardDef[] = [
  {
    label: 'Total de obrigações',
    special: 'total',
    colorClass: 'bg-white',
    borderClass: 'border-slate-200',
    textClass: 'text-slate-800',
  },
  {
    label: 'Em dia',
    status: 'Em dia',
    colorClass: 'bg-emerald-50',
    borderClass: 'border-emerald-200',
    textClass: 'text-emerald-700',
  },
  {
    label: 'A vencer',
    status: 'A vencer',
    colorClass: 'bg-amber-50',
    borderClass: 'border-amber-200',
    textClass: 'text-amber-700',
  },
  {
    label: 'Vencidas',
    status: 'Vencida',
    colorClass: 'bg-red-50',
    borderClass: 'border-red-200',
    textClass: 'text-red-700',
  },
  {
    label: 'Concluídas',
    status: 'Concluída',
    colorClass: 'bg-slate-50',
    borderClass: 'border-slate-200',
    textClass: 'text-slate-600',
  },
  {
    label: 'Críticas',
    special: 'critica',
    colorClass: 'bg-red-50',
    borderClass: 'border-red-300',
    textClass: 'text-red-800',
  },
];

export default function DashboardCards({ obligations }: Props) {
  const count = (card: CardDef): number => {
    if (card.special === 'total') return obligations.length;
    if (card.special === 'critica') return obligations.filter(o => o.priority === 'Crítica').length;
    return obligations.filter(o => o.status === card.status).length;
  };

  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
      {CARDS.map(card => (
        <div
          key={card.label}
          className={`rounded-lg border ${card.borderClass} ${card.colorClass} px-4 py-4 flex flex-col gap-1`}
        >
          <span className="text-xs font-medium text-slate-500 leading-tight">{card.label}</span>
          <span className={`text-3xl font-bold ${card.textClass}`}>{count(card)}</span>
        </div>
      ))}
    </div>
  );
}
