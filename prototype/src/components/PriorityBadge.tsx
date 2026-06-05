import { ObligationPriority } from '../types/obligation';

interface Props {
  priority: ObligationPriority;
}

const CONFIG: Record<ObligationPriority, { className: string }> = {
  'Baixa':   { className: 'bg-slate-100 text-slate-500 ring-1 ring-slate-200'  },
  'Média':   { className: 'bg-sky-50    text-sky-700   ring-1 ring-sky-200'    },
  'Alta':    { className: 'bg-orange-50 text-orange-700 ring-1 ring-orange-200' },
  'Crítica': { className: 'bg-red-100   text-red-800   ring-1 ring-red-300'    },
};

export default function PriorityBadge({ priority }: Props) {
  const { className } = CONFIG[priority] ?? CONFIG['Média'];
  return (
    <span className={`inline-flex items-center rounded px-2 py-0.5 text-xs font-medium ${className}`}>
      {priority}
    </span>
  );
}
