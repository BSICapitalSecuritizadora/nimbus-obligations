import type { ObligationFilters } from '../types/obligation';
import { EMISSIONS, OBLIGATION_TYPES, RESPONSIBLE_AREAS } from '../data/mockObligations';

interface Props {
  filters: ObligationFilters;
  onChange: (filters: ObligationFilters) => void;
  onClear: () => void;
}

const STATUSES = ['Em dia', 'A vencer', 'Vencida', 'Concluída', 'Em análise'];
const PRIORITIES = ['Baixa', 'Média', 'Alta', 'Crítica'];

function Select({
  label,
  value,
  onChange,
  options,
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
  options: string[];
}) {
  return (
    <div className="flex flex-col gap-1">
      <label className="text-xs font-medium text-slate-500">{label}</label>
      <select
        value={value}
        onChange={e => onChange(e.target.value)}
        className="rounded border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500"
      >
        <option value="">Todos</option>
        {options.map(o => (
          <option key={o} value={o}>{o}</option>
        ))}
      </select>
    </div>
  );
}

function DateInput({
  label,
  value,
  onChange,
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
}) {
  return (
    <div className="flex flex-col gap-1">
      <label className="text-xs font-medium text-slate-500">{label}</label>
      <input
        type="date"
        value={value}
        onChange={e => onChange(e.target.value)}
        className="rounded border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500"
      />
    </div>
  );
}

export default function ObligationFilters({ filters, onChange, onClear }: Props) {
  const set = (key: keyof ObligationFilters) => (value: string) =>
    onChange({ ...filters, [key]: value });

  const hasFilters = Object.values(filters).some(v => v !== '');

  return (
    <div className="rounded-lg border border-slate-200 bg-white px-5 py-4 mb-4">
      <div className="flex items-center justify-between mb-3">
        <span className="text-sm font-semibold text-slate-700">Filtros</span>
        {hasFilters && (
          <button
            onClick={onClear}
            className="text-xs text-brand-600 hover:text-brand-800 font-medium underline underline-offset-2"
          >
            Limpar filtros
          </button>
        )}
      </div>
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-3">
        <Select label="Emissão" value={filters.emission} onChange={set('emission')} options={EMISSIONS} />
        <Select label="Status" value={filters.status} onChange={set('status')} options={STATUSES} />
        <Select label="Tipo de obrigação" value={filters.obligationType} onChange={set('obligationType')} options={OBLIGATION_TYPES} />
        <Select label="Área responsável" value={filters.responsibleArea} onChange={set('responsibleArea')} options={RESPONSIBLE_AREAS} />
        <Select label="Prioridade" value={filters.priority} onChange={set('priority')} options={PRIORITIES} />
        <DateInput label="Vencimento de" value={filters.dueDateFrom} onChange={set('dueDateFrom')} />
        <DateInput label="Vencimento até" value={filters.dueDateTo} onChange={set('dueDateTo')} />
      </div>
    </div>
  );
}
