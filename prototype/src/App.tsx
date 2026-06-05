import { useState, useMemo } from 'react';
import { EmissionObligation, ObligationFilters } from './types/obligation';
import { MOCK_OBLIGATIONS } from './data/mockObligations';
import DashboardCards from './components/DashboardCards';
import ObligationFiltersPanel from './components/ObligationFilters';
import ObligationsTable from './components/ObligationsTable';
import ObligationDetailsModal from './components/ObligationDetailsModal';
import ObligationFormModal from './components/ObligationFormModal';

const EMPTY_FILTERS: ObligationFilters = {
  emission: '',
  status: '',
  obligationType: '',
  responsibleArea: '',
  priority: '',
  dueDateFrom: '',
  dueDateTo: '',
};

export default function App() {
  const [obligations, setObligations] = useState<EmissionObligation[]>(MOCK_OBLIGATIONS);
  const [filters, setFilters] = useState<ObligationFilters>(EMPTY_FILTERS);

  const [viewingId, setViewingId] = useState<string | null>(null);
  const [editingId, setEditingId] = useState<string | null>(null); // null = new, undefined = closed
  const [formOpen, setFormOpen] = useState(false);

  // ── derived ──────────────────────────────────────────────────────────────
  const filtered = useMemo(() => {
    return obligations.filter(o => {
      if (filters.emission && o.emission !== filters.emission) return false;
      if (filters.status && o.status !== filters.status) return false;
      if (filters.obligationType && o.obligationType !== filters.obligationType) return false;
      if (filters.responsibleArea && o.responsibleArea !== filters.responsibleArea) return false;
      if (filters.priority && o.priority !== filters.priority) return false;
      if (filters.dueDateFrom && o.dueDate < filters.dueDateFrom) return false;
      if (filters.dueDateTo && o.dueDate > filters.dueDateTo) return false;
      return true;
    });
  }, [obligations, filters]);

  const viewingObligation = viewingId ? obligations.find(o => o.id === viewingId) ?? null : null;
  const editingObligation = editingId ? obligations.find(o => o.id === editingId) ?? null : null;

  // ── handlers ─────────────────────────────────────────────────────────────
  function handleView(id: string) {
    setViewingId(id);
  }

  function handleEdit(id: string) {
    setEditingId(id);
    setFormOpen(true);
    setViewingId(null);
  }

  function handleNew() {
    setEditingId(null);
    setFormOpen(true);
  }

  function handleSave(data: EmissionObligation) {
    setObligations(prev => {
      const exists = prev.some(o => o.id === data.id);
      return exists ? prev.map(o => o.id === data.id ? data : o) : [data, ...prev];
    });
    setFormOpen(false);
    setEditingId(null);
  }

  function handleCloseForm() {
    setFormOpen(false);
    setEditingId(null);
  }

  function handleCloseDetail() {
    setViewingId(null);
  }

  function handleEditFromDetail() {
    if (viewingId) handleEdit(viewingId);
  }

  return (
    <div className="min-h-screen bg-slate-50">
      {/* ── top bar ── */}
      <header className="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div className="max-w-screen-2xl mx-auto px-6 py-3 flex items-center justify-between">
          <div className="flex items-center gap-3">
            {/* logo mark */}
            <div className="w-7 h-7 rounded bg-brand-700 flex items-center justify-center flex-shrink-0">
              <svg className="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <span className="text-sm font-semibold text-slate-800 tracking-tight">Gestão de Obrigações</span>
          </div>
          <span className="text-xs text-slate-400 bg-slate-100 rounded px-2 py-1 font-medium">
            Protótipo frontend — dados simulados
          </span>
        </div>
      </header>

      {/* ── page ── */}
      <main className="max-w-screen-2xl mx-auto px-6 py-6">
        {/* heading */}
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight">
            Gestão de Obrigações por Emissão
          </h1>
          <p className="text-sm text-slate-500 mt-1 max-w-3xl">
            Protótipo para acompanhamento de obrigações contratuais, prazos, responsáveis e evidências
            vinculadas a operações de securitização.
          </p>
        </div>

        {/* summary cards */}
        <DashboardCards obligations={obligations} />

        {/* filters */}
        <ObligationFiltersPanel
          filters={filters}
          onChange={setFilters}
          onClear={() => setFilters(EMPTY_FILTERS)}
        />

        {/* table header */}
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-sm font-semibold text-slate-700">
            Obrigações
            <span className="ml-1.5 text-slate-400 font-normal">({filtered.length})</span>
          </h2>
          <button
            onClick={handleNew}
            className="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-md bg-brand-700 text-white hover:bg-brand-800 transition-colors"
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            Nova obrigação
          </button>
        </div>

        {/* table */}
        <ObligationsTable
          obligations={filtered}
          onView={handleView}
          onEdit={handleEdit}
        />
      </main>

      {/* modals / drawer */}
      {viewingId && (
        <ObligationDetailsModal
          obligation={viewingObligation}
          onClose={handleCloseDetail}
          onEdit={handleEditFromDetail}
        />
      )}

      {formOpen && (
        <ObligationFormModal
          obligation={editingObligation}
          onClose={handleCloseForm}
          onSave={handleSave}
        />
      )}
    </div>
  );
}
