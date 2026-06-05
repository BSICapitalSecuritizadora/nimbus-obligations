import { EmissionObligation } from '../types/obligation';
import StatusBadge from './StatusBadge';
import PriorityBadge from './PriorityBadge';
import { formatDateBR } from '../utils/dateUtils';

interface Props {
  obligation: EmissionObligation | null;
  onClose: () => void;
  onEdit: () => void;
}

function Field({ label, value }: { label: string; value?: string }) {
  return (
    <div>
      <dt className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">{label}</dt>
      <dd className="text-sm text-slate-800">{value || '—'}</dd>
    </div>
  );
}

export default function ObligationDetailsModal({ obligation, onClose, onEdit }: Props) {
  if (!obligation) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-start justify-end">
      {/* backdrop */}
      <div
        className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"
        onClick={onClose}
      />
      {/* drawer */}
      <div className="relative z-10 flex flex-col h-full w-full max-w-2xl bg-white shadow-2xl overflow-y-auto">
        {/* header */}
        <div className="flex items-start justify-between px-6 py-5 border-b border-slate-200 sticky top-0 bg-white z-10">
          <div>
            <p className="text-xs font-medium text-slate-400 mb-0.5">{obligation.emission}{obligation.series ? ` · ${obligation.series}` : ''}</p>
            <h2 className="text-base font-semibold text-slate-900 leading-tight">{obligation.obligationType}</h2>
          </div>
          <div className="flex gap-2 items-center ml-4 flex-shrink-0">
            <button
              onClick={onEdit}
              className="text-xs font-medium px-3 py-1.5 rounded border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
            >
              Editar
            </button>
            <button
              onClick={onClose}
              className="text-slate-400 hover:text-slate-700 transition-colors p-1"
              aria-label="Fechar"
            >
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div className="flex-1 px-6 py-5 space-y-6">
          {/* status + priority row */}
          <div className="flex gap-3 flex-wrap">
            <div>
              <p className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Status</p>
              <StatusBadge status={obligation.status} />
            </div>
            <div>
              <p className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Prioridade</p>
              <PriorityBadge priority={obligation.priority} />
            </div>
          </div>

          {/* description */}
          <div>
            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Descrição</p>
            <p className="text-sm text-slate-800 leading-relaxed">{obligation.description}</p>
          </div>

          {/* grid of fields */}
          <dl className="grid grid-cols-2 gap-x-6 gap-y-4">
            <Field label="Emissão" value={obligation.emission} />
            <Field label="Série" value={obligation.series} />
            <Field label="Referência no Termo" value={obligation.termReference} />
            <Field label="Periodicidade" value={obligation.recurrence} />
            <Field label="Área responsável" value={obligation.responsibleArea} />
            <Field label="Responsável" value={obligation.responsiblePerson} />
            <Field label="Data de vencimento" value={formatDateBR(obligation.dueDate)} />
            <Field label="Última atualização" value={formatDateBR(obligation.lastUpdate)} />
          </dl>

          {/* evidence */}
          <div className="rounded-md border border-slate-200 bg-slate-50 p-4">
            <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Evidência exigida</p>
            <p className="text-sm text-slate-700 leading-relaxed">{obligation.requiredEvidence}</p>
          </div>

          {/* notes */}
          {obligation.notes && (
            <div className="rounded-md border border-amber-100 bg-amber-50 p-4">
              <p className="text-xs font-semibold text-amber-600 uppercase tracking-wide mb-1">Observações</p>
              <p className="text-sm text-amber-900 leading-relaxed">{obligation.notes}</p>
            </div>
          )}

          {/* history */}
          <div>
            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Histórico de alterações</p>
            <ol className="relative border-l border-slate-200 space-y-4 ml-2">
              {[...obligation.history].reverse().map((h, i) => (
                <li key={i} className="ml-4">
                  <span className="absolute -left-1.5 mt-1 w-3 h-3 rounded-full border-2 border-white bg-slate-300" />
                  <p className="text-xs font-medium text-slate-500">{formatDateBR(h.date)} · {h.user}</p>
                  <p className="text-sm text-slate-700 mt-0.5">{h.action}</p>
                </li>
              ))}
            </ol>
          </div>
        </div>
      </div>
    </div>
  );
}
