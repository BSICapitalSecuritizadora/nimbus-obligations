import { EmissionObligation } from '../types/obligation';
import StatusBadge from './StatusBadge';
import PriorityBadge from './PriorityBadge';
import { formatDateBR } from '../utils/dateUtils';

interface Props {
  obligations: EmissionObligation[];
  onView: (id: string) => void;
  onEdit: (id: string) => void;
}

export default function ObligationsTable({ obligations, onView, onEdit }: Props) {
  if (obligations.length === 0) {
    return (
      <div className="rounded-lg border border-slate-200 bg-white flex flex-col items-center justify-center py-20 text-center">
        <svg className="w-12 h-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p className="text-sm font-medium text-slate-500">Nenhuma obrigação encontrada</p>
        <p className="text-xs text-slate-400 mt-1">Tente ajustar os filtros ou limpe a seleção.</p>
      </div>
    );
  }

  return (
    <div className="rounded-lg border border-slate-200 bg-white overflow-hidden">
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 bg-slate-50 text-left">
              <th className="px-4 py-3 text-xs font-semibold text-slate-500 whitespace-nowrap">Emissão / Série</th>
              <th className="px-4 py-3 text-xs font-semibold text-slate-500 whitespace-nowrap">Tipo</th>
              <th className="px-4 py-3 text-xs font-semibold text-slate-500">Descrição</th>
              <th className="px-4 py-3 text-xs font-semibold text-slate-500 whitespace-nowrap">Responsável</th>
              <th className="px-4 py-3 text-xs font-semibold text-slate-500 whitespace-nowrap">Vencimento</th>
              <th className="px-4 py-3 text-xs font-semibold text-slate-500 whitespace-nowrap">Status</th>
              <th className="px-4 py-3 text-xs font-semibold text-slate-500 whitespace-nowrap">Prioridade</th>
              <th className="px-4 py-3 text-xs font-semibold text-slate-500 whitespace-nowrap">Ref. no Termo</th>
              <th className="px-4 py-3 text-xs font-semibold text-slate-500 whitespace-nowrap">Últ. atualização</th>
              <th className="px-4 py-3 text-xs font-semibold text-slate-500 whitespace-nowrap">Ações</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {obligations.map(ob => (
              <tr key={ob.id} className="hover:bg-slate-50 transition-colors">
                <td className="px-4 py-3 whitespace-nowrap">
                  <span className="font-medium text-slate-800 block text-xs leading-tight">{ob.emission}</span>
                  {ob.series && <span className="text-xs text-slate-400">{ob.series}</span>}
                </td>
                <td className="px-4 py-3 whitespace-nowrap">
                  <span className="text-xs text-slate-600">{ob.obligationType}</span>
                </td>
                <td className="px-4 py-3 max-w-xs">
                  <span className="text-xs text-slate-700 line-clamp-2 leading-relaxed">{ob.description}</span>
                </td>
                <td className="px-4 py-3 whitespace-nowrap">
                  <span className="text-xs text-slate-700 block">{ob.responsiblePerson}</span>
                  <span className="text-xs text-slate-400">{ob.responsibleArea}</span>
                </td>
                <td className="px-4 py-3 whitespace-nowrap">
                  <span className="text-xs text-slate-700">{formatDateBR(ob.dueDate)}</span>
                </td>
                <td className="px-4 py-3 whitespace-nowrap">
                  <StatusBadge status={ob.status} />
                </td>
                <td className="px-4 py-3 whitespace-nowrap">
                  <PriorityBadge priority={ob.priority} />
                </td>
                <td className="px-4 py-3 whitespace-nowrap">
                  <span className="text-xs text-slate-500 font-mono">{ob.termReference}</span>
                </td>
                <td className="px-4 py-3 whitespace-nowrap">
                  <span className="text-xs text-slate-500">{formatDateBR(ob.lastUpdate)}</span>
                </td>
                <td className="px-4 py-3 whitespace-nowrap">
                  <div className="flex gap-2">
                    <button
                      onClick={() => onView(ob.id)}
                      className="text-xs font-medium text-brand-600 hover:text-brand-800 transition-colors"
                    >
                      Visualizar
                    </button>
                    <span className="text-slate-300">|</span>
                    <button
                      onClick={() => onEdit(ob.id)}
                      className="text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors"
                    >
                      Editar
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="px-4 py-2.5 border-t border-slate-100 bg-slate-50 flex justify-between items-center">
        <span className="text-xs text-slate-400">
          {obligations.length} obrigação{obligations.length !== 1 ? 'ões' : ''} exibida{obligations.length !== 1 ? 's' : ''}
        </span>
      </div>
    </div>
  );
}
