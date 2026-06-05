# Gestão de Obrigações por Emissão

Protótipo frontend para acompanhamento de obrigações contratuais vinculadas a operações de securitização (CRI, CRA, Debêntures).

---

## O que é este projeto

Este é um **protótipo standalone** — sem backend, sem banco de dados e sem autenticação — desenvolvido para demonstrar o conceito de um módulo de gestão de obrigações oriundas de um Termo de Securitização.

Todos os dados exibidos são **simulados (mock data)**. Nenhuma informação é persistida além da sessão do navegador.

---

## Funcionalidades do protótipo

- **Dashboard com cards de resumo**: Total, Em dia, A vencer, Vencidas, Concluídas e Críticas
- **Tabela de obrigações** com filtros por Emissão, Status, Tipo, Área, Prioridade e Vencimento
- **Modal de detalhes** com todos os campos da obrigação e histórico de alterações
- **Formulário de criação e edição** com validação de campos obrigatórios
- **Estado vazio** quando filtros não retornam resultados
- **Layout responsivo** para desktop e notebooks
- Interface em **Português Brasileiro**

---

## Tecnologias utilizadas

| Tecnologia | Versão |
|---|---|
| React | 18 |
| TypeScript | 5 |
| Vite | 5 |
| Tailwind CSS | 3 |

---

## Como executar localmente

### Pré-requisitos

- Node.js 18+ instalado
- npm 9+

### Instalação

```bash
npm install
```

### Servidor de desenvolvimento

```bash
npm run dev
```

Acesse `http://localhost:5173` no navegador.

### Build de produção

```bash
npm run build
```

Os arquivos estáticos serão gerados na pasta `dist/`.

### Preview do build

```bash
npm run preview
```

---

## Deploy sugerido

### GitHub Pages

1. Faça push para um repositório público no GitHub.
2. Habilite GitHub Pages na aba **Settings > Pages**, apontando para a branch `main` e pasta `/dist` (ou configure o GitHub Actions para fazer o deploy automático).
3. Se necessário, ajuste o `base` no `vite.config.ts`:

```ts
export default defineConfig({
  base: '/nome-do-repositorio/',
  plugins: [react()],
})
```

### Azure Static Web Apps

1. Faça push para o GitHub.
2. No portal Azure, crie um **Static Web App** e conecte ao repositório.
3. Configure o workflow gerado pelo Azure com:
   - `app_location: "/"`
   - `output_location: "dist"`
4. O deploy ocorrerá automaticamente a cada push.

---

## Estrutura do projeto

```
src/
├── components/
│   ├── DashboardCards.tsx       # Cards de resumo no topo
│   ├── ObligationsTable.tsx     # Tabela principal de obrigações
│   ├── ObligationFilters.tsx    # Painel de filtros
│   ├── ObligationDetailsModal.tsx  # Drawer de visualização
│   ├── ObligationFormModal.tsx  # Modal de criação/edição
│   ├── StatusBadge.tsx          # Badge de status colorido
│   └── PriorityBadge.tsx        # Badge de prioridade colorido
├── data/
│   └── mockObligations.ts       # 18 obrigações simuladas + listas de opções
├── types/
│   └── obligation.ts            # Tipos TypeScript (EmissionObligation, etc.)
├── utils/
│   └── dateUtils.ts             # Formatação de datas (ISO → DD/MM/YYYY)
├── App.tsx                      # Componente raiz com estado global
├── main.tsx                     # Ponto de entrada
└── index.css                    # Estilos Tailwind
```

---

## O que está mockado

- **18 obrigações** cobrindo os tipos mais comuns encontrados em um Termo de Securitização
- **4 emissões**: CRI Residencial Aurora 2025, CRA Agro Safra Forte 2025, CRI Prime Offices 2024, Debêntures Infra Energia 2025
- **Histórico de alterações** por obrigação (simulado)
- **Estado React** para criação e edição (não persiste ao recarregar a página)

---

## O que seria necessário para produção

| Item | Descrição |
|---|---|
| Backend | API REST ou GraphQL para persistência das obrigações |
| Banco de dados | PostgreSQL, SQL Server ou similar |
| Autenticação | Azure AD / OAuth 2.0 com perfis de acesso |
| Permissões | Controle por papel (visualizador, editor, administrador) |
| Upload de evidências | Integração com Azure Blob Storage ou SharePoint |
| Alertas por e-mail | Notificações automáticas de vencimento (Azure Logic Apps ou similar) |
| Integração sistêmica | Conexão com o sistema interno da empresa |
| Extração automática de obrigações | Leitura e interpretação de Termos de Securitização via IA/OCR |
| Auditoria | Log de alterações com rastreabilidade por usuário |
| Relatórios | Exportação para PDF/Excel |

---

## Observação

Este protótipo foi desenvolvido exclusivamente para demonstração de conceito. Os dados são fictícios e não representam nenhuma operação real.
