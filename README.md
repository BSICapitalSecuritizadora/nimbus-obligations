# Nimbus Obligations

**Gestão de Obrigações por Emissão** — MVP para monitoramento de obrigações contratuais vinculadas a operações de securitização (CRI, CRA, Debêntures, Notas Comerciais e outros instrumentos de renda fixa estruturada).

---

## O que é este projeto

Sistema de gestão de obrigações oriundas de um **Termo de Securitização**, permitindo:

1. **Cadastrar operações** de securitização (CRI, CRA, Debêntures, etc.)
2. **Fazer upload do Termo de Securitização** (PDF)
3. **Extrair o texto** do PDF automaticamente
4. **Gerar obrigações sugeridas** com base no texto extraído (análise por palavras-chave, preparado para IA futura)
5. **Revisar, editar, aprovar ou rejeitar** cada sugestão
6. **Acompanhar o dashboard** de obrigações aprovadas com status, vencimentos e prioridades

> **Este protótipo** usa dados de demonstração e extração por mock (sem IA real). A estrutura está preparada para integração com Azure OpenAI ou Claude.

---

## Stack

| Componente | Versão |
|---|---|
| PHP | 8.2 |
| Laravel | 12 |
| Filament | 3 |
| Livewire | 3 |
| SQLite | (dev) / MySQL, PostgreSQL (prod) |
| Tailwind CSS | 4 |
| Vite | 7 |
| smalot/pdfparser | 2 |

---

## Pré-requisitos

- PHP 8.2+ com extensões: `sqlite3`, `pdo_sqlite`, `intl`, `fileinfo`, `mbstring`, `openssl`, `tokenizer`, `xml`
- Composer 2+
- Node.js 18+
- npm 9+

---

## Como executar localmente

```bash
# 1. Instalar dependências PHP
composer install

# 2. Copiar e configurar o .env
cp .env.example .env
php artisan key:generate

# 3. Criar banco SQLite e rodar migrations
touch database/database.sqlite
php artisan migrate

# 4. Popular com dados de demonstração
php artisan db:seed

# 5. Instalar dependências frontend e fazer build
npm install
npm run build

# 6. Link de armazenamento (para uploads)
php artisan storage:link

# 7. Iniciar servidor
php artisan serve
```

Acesse: **http://localhost:8000/admin**

**Login de demonstração:**
- E-mail: `admin@nimbus.local`
- Senha: `password`

---

## Como usar o fluxo de upload e extração

### 1. Cadastrar uma Operação
- Menu **Operações → Nova Operação**
- Preencha: nome, tipo (CRI, CRA, Debêntures...), emissora, agente fiduciário, datas

### 2. Fazer Upload do Termo de Securitização
- Menu **Documentos → Termos de Securitização → Fazer Upload de Termo**
- Selecione a operação e anexe o PDF (máx. 20 MB)
- Ou clique em **"Fazer Upload de Termo"** na tela de detalhe da operação

### 3. Processar o Documento
- Na lista de documentos, clique em **"Processar Documento"**
- O sistema extrai o texto do PDF e divide em chunks
- Status muda de *Pendente* → *Processado*
- Se o PDF for escaneado (sem texto), o status muda para *Falhou*

### 4. Gerar Obrigações Sugeridas
- Após o processamento, clique em **"Gerar Obrigações Sugeridas"**
- O sistema enfileira a geração e retorna imediatamente para a tela
- Execute um worker de filas em outro terminal para processar Gemini ou mock:

```bash
php artisan queue:work --timeout=600 --tries=1
```

- As sugestões ficam com status **"Sugerida"** e um badge aparece no menu

> Para extração Gemini, use `QUEUE_CONNECTION=database` ou `QUEUE_CONNECTION=redis`. Se `QUEUE_CONNECTION=sync`, o `dispatch()` do Laravel executa o job dentro da requisição Livewire e a tela pode voltar a aguardar a API externa.

### 5. Revisar e Aprovar
- Menu **Obrigações → Obrigações Sugeridas**
- Para cada sugestão: clique em **Aprovar**, **Rejeitar** ou **Editar** antes de aprovar
- Ao aprovar, uma obrigação é criada no dashboard com histórico

### 6. Acompanhar o Dashboard
- Menu **Dashboard** — exibe cards de resumo e tabela filtrável
- Filtre por operação, status, prioridade, área responsável, tipo

---

## Como funciona o processamento

### Extração de texto (TermDocumentTextExtractor)
- Usa `smalot/pdfparser` para ler o PDF
- Divide o texto em chunks de ~3.000 caracteres
- Tenta detectar referências de cláusulas automaticamente
- Salva o texto extraído e os chunks no banco de dados

### Extração de obrigações (MockObligationExtractor / GeminiObligationExtractor)
- A ação do Filament apenas limpa sugestões pendentes, marca a geração como `queued` e despacha `GenerateTermDocumentObligationsJob`
- O job chama `ObligationExtractionService`, processa chunks e salva novas sugestões fora da requisição Livewire
- `MockObligationExtractor` analisa o texto por palavras-chave em modo de demonstração
- `GeminiObligationExtractor` chama a API Gemini quando `OBLIGATION_EXTRACTOR=gemini`

---

## Estrutura principal

```
app/
├── Contracts/
│   └── ObligationExtractorInterface.php   ← Interface para extratores
├── Jobs/
│   ├── ProcessTermDocumentJob.php         ← Job de processamento de PDF
│   └── GenerateTermDocumentObligationsJob.php ← Job assíncrono de geração de obrigações
├── Models/
│   ├── Operation.php
│   ├── TermDocument.php
│   ├── TermDocumentChunk.php
│   ├── ExtractedObligation.php
│   ├── Obligation.php
│   └── ObligationHistory.php
├── Services/
│   ├── TermDocumentTextExtractor.php      ← Extração de texto via smalot/pdfparser
│   ├── MockObligationExtractor.php        ← Extração por palavras-chave (demo)
│   └── ObligationExtractionService.php   ← Orquestrador da extração
└── Filament/
    ├── Pages/
    │   └── ObligationsDashboard.php       ← Dashboard principal
    └── Resources/
        ├── OperationResource/
        ├── TermDocumentResource/
        ├── ExtractedObligationResource/   ← Obrigações sugeridas
        └── ObligationResource/            ← Obrigações aprovadas

database/migrations/
├── 2025_01_01_000001_create_operations_table.php
├── 2025_01_01_000002_create_term_documents_table.php
├── 2025_01_01_000003_create_term_document_chunks_table.php
├── 2025_01_01_000004_create_extracted_obligations_table.php
├── 2025_01_01_000005_create_obligations_table.php
└── 2025_01_01_000006_create_obligation_histories_table.php

prototype/                                 ← Protótipo React original (referência)
```

---

## Limitações atuais (MVP)

- **Sem OCR real** — PDFs escaneados (imagem) não são extraídos; apenas PDFs com texto nativo
- **Extração por palavras-chave** — não usa IA; identifica obrigações por padrões simples
- **Sem notificações** — não há alertas de vencimento por e-mail/SMS
- **Sem upload de evidências** — apenas registro da evidência exigida
- **Autenticação simplificada** — sem perfis de acesso ou permissões por papel
- **Fila obrigatória para Gemini** — mantenha um worker ativo para que a geração de obrigações não rode dentro do Livewire

---

## Melhorias futuras

| Melhoria | Descrição |
|---|---|
| Azure Document Intelligence | OCR real para PDFs escaneados |
| Azure OpenAI / Claude | Extração de obrigações por IA generativa |
| Upload de evidências | Armazenamento de comprovantes por obrigação |
| Notificações de vencimento | E-mail/push com alertas de prazo |
| Permissões por papel | Administrador, Gestor, Visualizador |
| Integração sistêmica | Conexão com sistema interno da empresa |
| Calendário de obrigações | Visão de calendário mensal |
| Exportação | PDF e Excel do dashboard |
| Azure Blob Storage | Armazenamento de PDFs no Azure |
| Worker de filas | Processamento assíncrono em produção |

---

## Deploy sugerido (Azure)

1. **Azure App Service** — para o backend Laravel (PHP 8.2)
2. **Azure Database for MySQL** — substituir SQLite em produção
3. **Azure Blob Storage** — para armazenamento de PDFs (já preparado via `Storage::disk()`)
4. **Azure Static Web Apps** — para o frontend (se desacoplado futuramente)

### Variáveis de ambiente para produção

```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=nimbus_obligations
DB_USERNAME=...
DB_PASSWORD=...
FILESYSTEM_DISK=azure     # ou s3, ou local
AZURE_STORAGE_ACCOUNT=...
AZURE_STORAGE_KEY=...
AZURE_STORAGE_CONTAINER=termo-docs
QUEUE_CONNECTION=redis    # ou database
```

### Variáveis de ambiente para geração por Gemini

```env
QUEUE_CONNECTION=database
OBLIGATION_EXTRACTOR=gemini
GEMINI_API_TIMEOUT=30
GEMINI_MAX_CHUNK_CHARS=8000
GEMINI_MAX_CHUNKS_PER_DOCUMENT=3
```

---

## Desenvolvimento

```bash
# Servidor de desenvolvimento com hot reload
npm run dev

# Em outro terminal
php artisan serve

# Reset e re-seed do banco
php artisan migrate:fresh --seed

# Testes
php artisan test
```
