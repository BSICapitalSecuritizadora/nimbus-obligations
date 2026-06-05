import { ObligationStatus } from '../types/obligation';

interface Props {
  status: ObligationStatus;
}

const CONFIG: Record<ObligationStatus, { label: string; className: string }> = {
  'Em dia':    { label: 'Em dia',    className: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
  'A vencer':  { label: 'A vencer',  className: 'bg-amber-50  text-amber-700  ring-1 ring-amber-200'  },
  'Vencida':   { label: 'Vencida',   className: 'bg-red-50    text-red-700    ring-1 ring-red-200'    },
  'Concluída': { label: 'Concluída', className: 'bg-slate-100 text-slate-600  ring-1 ring-slate-300'  },
  'Em análise':{ label: 'Em análise',className: 'bg-blue-50   text-blue-700   ring-1 ring-blue-200'   },
};

export default function StatusBadge({ status }: Props) {
  const { label, className } = CONFIG[status] ?? CONFIG['Em análise'];
  return (
    <span className={`inline-flex items-center rounded px-2 py-0.5 text-xs font-medium ${className}`}>
      {label}
    </span>
  );
}
