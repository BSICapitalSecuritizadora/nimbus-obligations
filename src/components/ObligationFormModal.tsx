import { useState, useEffect, FormEvent } from 'react';
import { EmissionObligation, ObligationStatus, ObligationPriority } from '../types/obligation';
import { EMISSIONS, OBLIGATION_TYPES, RESPONSIBLE_AREAS, RECURRENCES } from '../data/mockObligations';

interface Props {
  obligation: EmissionObligation | null; // null = new
  onClose: () => void;
  onSave: (data: EmissionObligation) => void;
}

const STATUSES: ObligationStatus[] = ['Em dia', 'A vencer', 'Vencida', 'Concluída', 'Em análise'];
const PRIORITIES: ObligationPriority[] = ['Baixa', 'Média', 'Alta', 'Crítica'];

function newId(): string {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
}

function todayISO(): string {
  return new Date().toISOString().slice(0, 10);
}

function emptyForm(): EmissionObligation {
  return {
    id: newId(),
    emission: '',
    series: '',
    obligationType: '',
    description: '',
    responsibleArea: '',
    responsiblePerson: '',
    dueDate: '',
    recurrence: 'Mensal',
    status: 'A vencer',
    priority: 'Média',
    termReference: '',
    requiredEvidence: '',
    lastUpdate: todayISO(),
    notes: '',
    history: [{ date: todayISO(), user: 'Usuário', action: 'Obrigação criada manualmente.' }],
  };
}

type FieldKey = keyof Omit<EmissionObligation, 'history'>;

function Label({ children }: { children: React.ReactNode }) {
  return <label className="block text-xs font-semibold text-slate-500 mb-1">{children}</label>;
}

function inputCls(error?: boolean) {
  return `w-full rounded border ${error ? 'border-red-300' : 'border-slate-200'} px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500`;
}

export default function ObligationFormModal({ obligation, onClose, onSave }: Props) {
  const isEdit = obligation !== null;
  const [form, setForm] = useState<EmissionObligation>(obligation ?? emptyForm());
  const [errors, setErrors] = useState<Partial<Record<FieldKey, string>>>({});

  useEffect(() => {
    setForm(obligation ?? emptyForm());
    setErrors({});
  }, [obligation]);

  function set(key: FieldKey) {
    return (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
      setForm(prev => ({ ...prev, [key]: e.target.value }));
      setErrors(prev => ({ ...prev, [key]: undefined }));
    };
  }

  function validate(): boolean {
    const errs: Partial<Record<FieldKey, string>> = {};
    if (!form.emission) errs.emission = 'Campo obrigatório';
    if (!form.obligationType) errs.obligationType = 'Campo obrigatório';
    if (!form.description) errs.description = 'Campo obrigatório';
    if (!form.responsibleArea) errs.responsibleArea = 'Campo obrigatório';
    if (!form.responsiblePerson) errs.responsiblePerson = 'Campo obrigatório';
    if (!form.dueDate) errs.dueDate = 'Campo obrigatório';
    if (!form.termReference) errs.termReference = 'Campo obrigatório';
    if (!form.requiredEvidence) errs.requiredEvidence = 'Campo obrigatório';
    setErrors(errs);
    return Object.keys(errs).length === 0;
  }

  function handleSubmit(e: FormEvent) {
    e.preventDefault();
    if (!validate()) return;

    const saved: EmissionObligation = {
      ...form,
      lastUpdate: todayISO(),
      history: isEdit
        ? [
            ...form.history,
            { date: todayISO(), user: 'Usuário', action: 'Obrigação editada.' },
          ]
        : form.history,
    };
    onSave(saved);
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={onClose} />
      <div className="relative z-10 w-full max-w-2xl max-h-[90vh] flex flex-col bg-white rounded-xl shadow-2xl overflow-hidden">
        {/* header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200">
          <h2 className="text-base font-semibold text-slate-900">
            {isEdit ? 'Editar obrigação' : 'Nova obrigação'}
          </h2>
          <button
            onClick={onClose}
            className="text-slate-400 hover:text-slate-700 transition-colors"
            aria-label="Fechar"
          >
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {/* body */}
        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto px-6 py-5 space-y-4">
          {/* row 1 */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label>Emissão *</Label>
              <select value={form.emission} onChange={set('emission')} className={inputCls(!!errors.emission)}>
                <option value="">Selecione</option>
                {EMISSIONS.map(e => <option key={e} value={e}>{e}</option>)}
              </select>
              {errors.emission && <p className="text-xs text-red-500 mt-0.5">{errors.emission}</p>}
            </div>
            <div>
              <Label>Série</Label>
              <input type="text" value={form.series ?? ''} onChange={set('series')} className={inputCls()} placeholder="Ex.: 1ª Série" />
            </div>
          </div>

          {/* row 2 */}
          <div>
            <Label>Tipo de obrigação *</Label>
            <select value={form.obligationType} onChange={set('obligationType')} className={inputCls(!!errors.obligationType)}>
              <option value="">Selecione</option>
              {OBLIGATION_TYPES.map(t => <option key={t} value={t}>{t}</option>)}
            </select>
            {errors.obligationType && <p className="text-xs text-red-500 mt-0.5">{errors.obligationType}</p>}
          </div>

          {/* description */}
          <div>
            <Label>Descrição *</Label>
            <textarea
              rows={3}
              value={form.description}
              onChange={set('description')}
              className={inputCls(!!errors.description)}
              placeholder="Descreva a obrigação contratual..."
            />
            {errors.description && <p className="text-xs text-red-500 mt-0.5">{errors.description}</p>}
          </div>

          {/* row 3 */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label>Área responsável *</Label>
              <select value={form.responsibleArea} onChange={set('responsibleArea')} className={inputCls(!!errors.responsibleArea)}>
                <option value="">Selecione</option>
                {RESPONSIBLE_AREAS.map(a => <option key={a} value={a}>{a}</option>)}
              </select>
              {errors.responsibleArea && <p className="text-xs text-red-500 mt-0.5">{errors.responsibleArea}</p>}
            </div>
            <div>
              <Label>Responsável *</Label>
              <input type="text" value={form.responsiblePerson} onChange={set('responsiblePerson')} className={inputCls(!!errors.responsiblePerson)} placeholder="Nome do responsável" />
              {errors.responsiblePerson && <p className="text-xs text-red-500 mt-0.5">{errors.responsiblePerson}</p>}
            </div>
          </div>

          {/* row 4 */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label>Data de vencimento *</Label>
              <input type="date" value={form.dueDate} onChange={set('dueDate')} className={inputCls(!!errors.dueDate)} />
              {errors.dueDate && <p className="text-xs text-red-500 mt-0.5">{errors.dueDate}</p>}
            </div>
            <div>
              <Label>Periodicidade</Label>
              <select value={form.recurrence} onChange={set('recurrence')} className={inputCls()}>
                {RECURRENCES.map(r => <option key={r} value={r}>{r}</option>)}
              </select>
            </div>
          </div>

          {/* row 5 */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label>Status</Label>
              <select value={form.status} onChange={set('status')} className={inputCls()}>
                {STATUSES.map(s => <option key={s} value={s}>{s}</option>)}
              </select>
            </div>
            <div>
              <Label>Prioridade</Label>
              <select value={form.priority} onChange={set('priority')} className={inputCls()}>
                {PRIORITIES.map(p => <option key={p} value={p}>{p}</option>)}
              </select>
            </div>
          </div>

          {/* row 6 */}
          <div>
            <Label>Referência no Termo *</Label>
            <input type="text" value={form.termReference} onChange={set('termReference')} className={inputCls(!!errors.termReference)} placeholder="Ex.: Cláusula 8.1.2" />
            {errors.termReference && <p className="text-xs text-red-500 mt-0.5">{errors.termReference}</p>}
          </div>

          {/* evidence */}
          <div>
            <Label>Evidência exigida *</Label>
            <textarea
              rows={2}
              value={form.requiredEvidence}
              onChange={set('requiredEvidence')}
              className={inputCls(!!errors.requiredEvidence)}
              placeholder="Descreva os documentos ou comprovantes exigidos..."
            />
            {errors.requiredEvidence && <p className="text-xs text-red-500 mt-0.5">{errors.requiredEvidence}</p>}
          </div>

          {/* notes */}
          <div>
            <Label>Observações</Label>
            <textarea rows={2} value={form.notes ?? ''} onChange={set('notes')} className={inputCls()} placeholder="Observações adicionais (opcional)..." />
          </div>
        </form>

        {/* footer */}
        <div className="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 bg-slate-50">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 text-sm font-medium rounded border border-slate-200 text-slate-600 hover:bg-white transition-colors"
          >
            Cancelar
          </button>
          <button
            type="button"
            onClick={(e) => { e.preventDefault(); handleSubmit(e as unknown as FormEvent); }}
            className="px-4 py-2 text-sm font-medium rounded bg-brand-700 text-white hover:bg-brand-800 transition-colors"
          >
            {isEdit ? 'Salvar alterações' : 'Criar obrigação'}
          </button>
        </div>
      </div>
    </div>
  );
}
