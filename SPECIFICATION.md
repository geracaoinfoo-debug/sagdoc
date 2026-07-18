# SAGDOC — Especificação Técnica de Implementação

**Versão:** 1.0 · **Destino:** implementação assistida (Claude Code)
**Projeto:** Sistema de Apoio à Gestão Documental dos Processos de Despacho Aduaneiro — Guiné-Bissau
**Base:** monografia de Valdano Henrique Barbosa (2026)

Este documento é a fonte única de verdade para implementar o SAGDOC. Está escrito para ser
executado passo a passo. Cada requisito funcional traz o código `RFxx`, cada não-funcional
`RNFxx` e cada regra de negócio `RNxx`, para rastreabilidade com a monografia.

---

## 1. Objetivo e âmbito

Construir uma aplicação web que **complementa o SYDONIA (ASYCUDA)** gerindo o dossiê
documental de cada processo de despacho. O SYDONIA continua responsável pelo cálculo de
direitos e emissão da DU; o SAGDOC recebe o **nº da DU** como chave de negócio e gere os
documentos, a tramitação, os prazos e a auditoria associados.

**Fora de âmbito:** integração técnica direta com o SYDONIA (o nº de DU é introduzido
manualmente pelo despachante); cálculo de direitos aduaneiros.

---

## 2. Stack e decisões de arquitetura

- **Backend:** PHP 8.2, arquitetura **MVC própria** (sem framework pesado), roteamento por
  front controller `public/index.php` + `.htaccess`.
- **Base de dados:** MySQL 8.0, motor InnoDB, `utf8mb4_unicode_ci`.
- **Frontend:** HTML5 + CSS3 + Bootstrap 5.3 + JavaScript ES6+. Bibliotecas: Chart.js,
  PDF.js, DataTables, SweetAlert2.
- **Acesso a dados:** PDO com **prepared statements** obrigatórios (RNF13).
- **Dependências (Composer):** `phpmailer/phpmailer`, `firebase/php-jwt`, `monolog/monolog`.
- **Padrões:** código modular (RNF20), comentado (RNF19), em Git (RNF21).

### 2.1 Camadas

```
HTTP → public/index.php (Router)
        → Middleware (Session → Auth → RBAC → CSRF → RateLimit)
          → Controller → Service (regras de negócio) → Model (PDO) → MySQL
        ← View (template PHP) / JSON
```

- **Controllers**: recebem request, validam, delegam a Services, devolvem View/JSON.
- **Services**: `WorkflowService` (transições de estado), `SLAService`, `ChecklistService`,
  `NotificationService`, `AuditService`, `ReportService`, `AuthService`.
- **Models**: uma classe por tabela, com métodos CRUD e queries específicas.
- **Core**: `Router`, `Database` (singleton PDO), `Request`, `Response`, `Session`, `View`.

---

## 3. Modelo de dados (MySQL)

Criar exatamente as tabelas abaixo (do capítulo de implementação da monografia). Tipos,
enums e índices devem ser respeitados.

### 3.1 `usuarios`
`id` PK AI · `username` VARCHAR(50) UNIQUE · `password_hash` VARCHAR(255) · `nome` VARCHAR(100)
· `email` VARCHAR(100) UNIQUE · `telefone` VARCHAR(20) · `perfil`
ENUM(`despachante`,`verificador`,`chefe_setor`,`gestor`,`administrador`,`consultor`) ·
`ativo` BOOL default TRUE · `data_criacao` TS · `ultimo_acesso` TS NULL. Índices: `perfil`, `ativo`.
> Nota: acrescentar `consultor` ao ENUM (RF01 lista o perfil Consultor read-only).

### 3.2 `despachantes` (extends usuarios)
`usuario_id` PK/FK→usuarios · `nif` UNIQUE · `numero_licenca` UNIQUE · `data_validade_licenca` DATE.

### 3.3 `verificadores` (extends usuarios)
`usuario_id` PK/FK→usuarios · `matricula` UNIQUE · `setor` VARCHAR(50).

### 3.4 `importadores`
`id` PK AI · `nome` VARCHAR(150) · `nif` UNIQUE · `endereco` TEXT · `telefone` · `email` ·
`data_cadastro` TS. Índice: `nif`.

### 3.5 `processos_documentais`
`id` PK AI · `numero_du` VARCHAR(20) UNIQUE · `despachante_id` FK→usuarios ·
`importador_id` FK→importadores · `categoria` VARCHAR(50) · `regime` VARCHAR(30) ·
`status` ENUM(`rascunho`,`submetido`,`aguardando_distribuicao`,`em_verificacao`,
`aguardando_documentos`,`aprovado_verificador`,`aprovado_final`,`rejeitado`,`cancelado`)
default `rascunho` · `verificador_id` FK NULL · `prioridade` ENUM(`normal`,`operador_confiavel`)
default `normal` · `tentativas_submissao` INT default 0 · `data_criacao` · `data_submissao` NULL
· `data_distribuicao` NULL · `data_aprovacao_verificador` NULL · `data_aprovacao_final` NULL ·
`parecer_tecnico` TEXT NULL · `motivo_rejeicao` TEXT NULL · `observacoes` TEXT.
Índices: `numero_du`, `status`, `despachante_id`, `verificador_id`, `data_submissao`.
> Campos `prioridade` (RN10) e `tentativas_submissao` (RN05) acrescentados ao esquema base.

### 3.6 `tipos_documentos`
`id` PK AI · `nome` VARCHAR(100) · `descricao` TEXT · `formatos_aceites` JSON ·
`obrigatorio_para` JSON · `validade_meses` INT NULL · `ativo` BOOL default TRUE.
> `validade_meses` acrescentado para suportar RN08/RN09.

Registos iniciais (RN02):

| nome | obrigatorio_para |
|---|---|
| Fatura Comercial | `["*"]` |
| Conhecimento de Embarque (B/L) | `["*"]` |
| Lista de Embalagem (Packing List) | `["*"]` |
| Certificado Sanitário | `["alimentos","animais"]` |
| Certificado Fitossanitário | `["vegetais"]` |
| Licença de Importação | `["medicamentos","quimicos","armas"]` |
| Certificado de Origem | `[]` |

### 3.7 `documentos`
`id` PK AI · `processo_id` FK (ON DELETE CASCADE) · `tipo_documento_id` FK · `nome_arquivo`
VARCHAR(255) · `caminho_arquivo` VARCHAR(500) · `tamanho_bytes` INT · `hash_sha256` CHAR(64) ·
`data_validade` DATE NULL · `data_upload` TS · `upload_por` FK→usuarios · `verificado` BOOL default FALSE.
Índices: `processo_id`, `tipo_documento_id`.

### 3.8 `historico_tramitacao`
`id` PK AI · `processo_id` FK CASCADE · `usuario_id` FK · `data_hora` TS · `acao` VARCHAR(255) ·
`status_anterior` VARCHAR(30) · `status_novo` VARCHAR(30) · `observacao` TEXT. Índices: `processo_id`, `data_hora`.

### 3.9 `comunicacoes`
`id` PK AI · `processo_id` FK CASCADE · `remetente_id` FK · `destinatario_id` FK · `data_hora` TS ·
`assunto` VARCHAR(255) · `mensagem` TEXT · `lida` BOOL default FALSE. Índices: `processo_id`, `destinatario_id`, `lida`.

### 3.10 `notificacoes`
`id` PK AI · `usuario_id` FK CASCADE · `tipo` VARCHAR(50) · `titulo` VARCHAR(150) · `mensagem` TEXT ·
`link_referencia` VARCHAR(255) · `data_hora` TS · `lida` BOOL default FALSE. Índices: `usuario_id`, `lida`, `data_hora`.

### 3.11 `logs_auditoria` (imutável — RN12)
`id` BIGINT PK AI · `data_hora` TS · `usuario_id` FK ON DELETE SET NULL · `acao` VARCHAR(100) ·
`entidade_afetada` VARCHAR(50) · `id_entidade` INT · `ip_origem` VARCHAR(45) · `user_agent` TEXT ·
`detalhes` JSON. Índices: `usuario_id`, `data_hora`, `acao`.
> Sem UPDATE/DELETE pela aplicação. Conceder ao utilizador de BD apenas INSERT/SELECT nesta tabela.

### 3.12 `configuracoes`
`chave` VARCHAR(50) PK · `valor` TEXT · `tipo` ENUM(`string`,`int`,`bool`,`json`) · `descricao` TEXT ·
`ultima_alteracao` TS ON UPDATE.

Registos iniciais: `sla_distribuicao_horas=4`, `sla_verificacao_horas=48`,
`sla_aprovacao_chefe_horas=24`, `tamanho_max_arquivo_mb=10`, `email_notificacoes=true`,
`modo_manutencao=false`.

### 3.13 Procedures, triggers e views (obrigatórios)

- **Procedure `atribuir_verificador_automatico(p_processo_id)`** — seleciona o verificador com
  menos processos em `em_verificacao`/`aguardando_documentos`, atualiza o processo para
  `em_verificacao` e insere no histórico (RF15).
- **Trigger `tr_processo_status_change`** — `AFTER UPDATE` em `processos_documentais`: se o
  status mudou, insere linha em `historico_tramitacao` (garante rastreabilidade mesmo em
  alterações diretas).
- **View `v_processos_completos`** — junta processo + despachante + importador + verificador +
  `TIMESTAMPDIFF(HOUR, data_submissao, COALESCE(data_aprovacao_final, NOW()))` como
  `horas_tramitacao`.
- **View `v_estatisticas_verificador`** — total, em_verificacao, aprovados e tempo médio por verificador.

O SQL literal destas rotinas está no capítulo de implementação da monografia e deve ser
transcrito para `database/schema.sql`.

---

## 4. Requisitos funcionais (RF) — o que implementar

### Módulo 1 — Utilizadores e autenticação
- **RF01** Cadastro com perfis: Administrador, Gestor DGA, Chefe de Setor, Verificador, Despachante, Consultor.
- **RF02** Login por utilizador + senha; **2FA opcional** para perfis administrativos.
- **RF03** RBAC conforme [§8](#8-matriz-de-permissões-rbac).
- **RF04** Recuperação de senha por email com link temporário.
- **RF05** Auditoria de acesso: registar cada login (timestamp, IP, sucesso/falha).

### Módulo 2 — Gestão de processos
- **RF06** Criar processo: nº DU, importador, categoria, regime.
- **RF07** Validar formato do nº DU: regex `^20\d{2}\/\d{6}$` (ex.: `2025/001234`).
- **RF08** Checklist dinâmico de documentos por categoria + regime.
- **RF09** Upload PDF/JPG/PNG, ≤10MB/arquivo, múltiplos por tipo.
- **RF10** Captura por câmara do dispositivo (mobile-first, opcional).
- **RF11** Pré-visualização antes da submissão (PDF.js/imagem).
- **RF12** Editar processo (adicionar/substituir/remover documentos) enquanto `rascunho`.
- **RF13** Submeter → transição automática para `aguardando_distribuicao`.
- **RF14** Recibo digital: timestamp, nº protocolo, lista de documentos.

### Módulo 3 — Workflow
- **RF15** Distribuição automática (balanceamento por carga; procedure).
- **RF16** Redistribuição manual pelo Chefe de Setor.
- **RF17** Fila do verificador ordenada por submissão (FIFO) ou prioridade.
- **RF18** Visualizar documentos (zoom, rotação, navegação de páginas).
- **RF19** Verificação de checklist (marca submetidos, destaca em falta).
- **RF20** Solicitar documentos adicionais (o quê + prazo).
- **RF21** Notificar despachante da solicitação (email + in-app).
- **RF22** Resposta do despachante (reanexa) → volta à fila do verificador.
- **RF23** Aprovar (parecer técnico) → `aprovado_verificador`.
- **RF24** Rejeitar (motivos) → volta ao despachante.
- **RF25** Aprovação final do Chefe → `aprovado_final`.
- **RF26** Controlo de SLA por estado + alerta ao ultrapassar.

### Módulo 4 — Comunicação
- **RF27** Mensagens internas no contexto do processo.
- **RF28** Histórico de comunicação (timestamp, autor).
- **RF29** Emails para eventos críticos (submissão, solicitação, aprovação, rejeição).
- **RF30** Notificações in-app no dashboard.

### Módulo 5 — Consulta e pesquisa
- **RF31** Pesquisa por nº DU, importador, despachante, data, status.
- **RF32** Filtros combinados.
- **RF33** Detalhe do processo (dados, documentos, histórico, comunicações).
- **RF34** Download de documento individual ou dossiê completo (ZIP).
- **RF35** Timeline de operações (quem, o quê, quando).

### Módulo 6 — Relatórios e dashboards
- **RF36** Dashboard Despachante.
- **RF37** Dashboard Verificador.
- **RF38** Dashboard Chefe de Setor.
- **RF39** Dashboard Gerencial (DGA).
- **RF40** Relatórios customizados + exportar PDF/Excel.
- **RF41** KPIs: tempo médio, % dentro do SLA, taxa de rejeição, retrabalho.

### Módulo 7 — Administração
- **RF42** Gerir tipos de documentos e associação a categorias.
- **RF43** Gerir categorias de mercadoria e regras.
- **RF44** Configurar SLA por fase.
- **RF45** Gerir utilizadores (criar/editar/desativar, perfis).
- **RF46** Logs de auditoria (só administradores).
- **RF47** Backup e restauração de BD e ficheiros.

---

## 5. Requisitos não-funcionais (RNF)

**Desempenho:** RNF01 páginas <3s a 2 Mbps · RNF02 upload múltiplo com barra de progresso ·
RNF03 ≥200 utilizadores concorrentes · RNF04 ≥100 000 processos e 1 000 000 documentos.
**Usabilidade:** RNF05 design centrado no utilizador · RNF06 responsivo (desktop/tablet/telemóvel) ·
RNF07 PT principal, estrutura pronta para FR/EN · RNF08 WCAG 2.0 A (contraste, teclado, alt-text) ·
RNF09 ajuda contextual + manual.
**Segurança:** RNF10 AES-256 para dados sensíveis · RNF11 HTTPS TLS 1.2+ · RNF12 bcrypt/argon2 +
salt · RNF13 proteção contra SQLi/XSS/CSRF/força-bruta · RNF14 logs de segurança imutáveis ·
RNF15 backup diário geograficamente separado.
**Confiabilidade:** RNF16 99% em expediente (8h–18h úteis) · RNF17 recuperação automática ·
RNF18 upload retomável.
**Manutenibilidade:** RNF19 código limpo e comentado · RNF20 modular · RNF21 Git.
**Portabilidade:** RNF22 SO-independente · RNF23 Chrome/Firefox/Safari/Edge recentes.
**Conformidade:** RNF24 Código Aduaneiro CEDEAO + nacional · RNF25 proteção de dados pessoais ·
RNF26 documentos com timestamp+hash para validade legal.

---

## 6. Regras de negócio (RN) — lógica obrigatória

- **RN01** Um único processo por nº de DU (constraint UNIQUE + verificação na criação).
- **RN02** Documentos obrigatórios por categoria (ver tabela §3.6): Todas → Fatura+B/L+Packing;
  Alimentos → +Sanitário; Vegetais → +Fitossanitário; Medicamentos → +Licença; Químicos →
  +MSDS/Licença; Veículos → +Documento de origem; Bebidas alcoólicas → +Licença DGCI.
- **RN03** Só submete se todos os obrigatórios estiverem anexados.
- **RN04** Prazo de 5 dias úteis para responder a solicitação; expirado → `pendente/aguardando`.
- **RN05** Rejeitado pode reenviar até **3 vezes**; à 3ª rejeição exige intervenção do Chefe
  (usar `tentativas_submissao`).
- **RN06** Hierarquia: verificador aprova (técnica) **antes** do chefe (final).
- **RN07** Aprovação final é **irreversível** (só Administrador reabre, com justificativa registada).
- **RN08** Alertar documentos com validade próxima do vencimento/expirados (`data_validade`).
- **RN09** Documentos de validade estendida podem ser **reutilizados** (linkados) noutros
  processos do mesmo importador sem reupload.
- **RN10** Importador 100% conforme nos últimos 12 meses → sinalizar **operador confiável** e
  prioridade na fila (`prioridade`).
- **RN11** SLA padrão: distribuição 4h · verificação 48h · aprovação chefe 24h · **total 72h**.
- **RN12** Auditoria **imutável** (nunca apagar; apenas arquivar após período legal).
- **RN13** Qualidade: ≥150 DPI, PDF/JPG/PNG, ≤10MB, multipágina de preferência em PDF único.
- **RN14** Acesso por propriedade: despachante vê os seus; verificador os atribuídos; chefe os do
  setor; gestor todos. **Aplicar em TODAS as queries de leitura.**
- **RN15** Notificações automáticas conforme evento e destinatário (ver §11).

---

## 7. Atores e casos de uso

Atores: Despachante, Verificador, Chefe de Setor, Gestor DGA, Administrador; sistemas externos
SYDONIA (referência) e Email (notificações).

**UC02 — Criar Processo** (Despachante): formulário → valida DU (RF07) → gera checklist (RF08) →
cria em `rascunho` → redireciona para upload. Extensões: DU inválida → erro; DU já existente →
oferecer consultar processo.
**UC10 — Visualizar Documentos** (Verificador): abre da fila → lista documentos → visualizador
(zoom/rotação) → marcar “verificado”. Extensão: documento corrompido → solicitar reenvio.
**UC12 — Aprovar Processo** (Verificador): “Aprovar” → insere parecer → `aprovado_verificador`
→ notifica Chefe e Despachante.

---

## 8. Matriz de permissões (RBAC)

`✔ = permitido`. Aplicar via middleware **e** filtrar dados por propriedade (RN14).

| Ação | Despachante | Verificador | Chefe Setor | Gestor | Admin | Consultor |
|---|:--:|:--:|:--:|:--:|:--:|:--:|
| Criar processo | ✔ | | | | | |
| Upload/editar docs (rascunho) | ✔ | | | | | |
| Submeter processo | ✔ | | | | | |
| Responder solicitação | ✔ | | | | | |
| Ver fila / analisar | | ✔ | | | | |
| Aprovar/rejeitar (técnica) | | ✔ | | | | |
| Solicitar documentos | | ✔ | | | | |
| Distribuir/redistribuir | | | ✔ | | | |
| Aprovação final | | | ✔ | | | |
| Ver processos do setor | | | ✔ | | | |
| Ver todos os processos | | | | ✔ | ✔ | ✔ (leitura) |
| Relatórios/KPIs | | | ✔ | ✔ | ✔ | ✔ (leitura) |
| Gerir utilizadores | | | | | ✔ | |
| Gerir tipos doc / categorias | | | | | ✔ | |
| Configurar SLA | | | | | ✔ | |
| Ver logs de auditoria | | | | | ✔ | |
| Backup/restauração | | | | | ✔ | |
| Reabrir processo final (RN07) | | | | | ✔ | |

---

## 9. Máquina de estados do processo

Estados: `rascunho`, `submetido`, `aguardando_distribuicao`, `em_verificacao`,
`aguardando_documentos`, `aprovado_verificador`, `aprovado_final`, `rejeitado`, `cancelado`.

Transições permitidas (implementar em `WorkflowService`, rejeitando qualquer outra):

| De | Para | Ação / ator |
|---|---|---|
| rascunho | aguardando_distribuicao | Submeter (Despachante, RF13, exige RN03) |
| rascunho | cancelado | Cancelar (Despachante) |
| aguardando_distribuicao | em_verificacao | Distribuir auto/manual (Sistema/Chefe, RF15/16) |
| em_verificacao | aprovado_verificador | Aprovar (Verificador, RF23) |
| em_verificacao | aguardando_documentos | Solicitar docs (Verificador, RF20) |
| em_verificacao | rejeitado | Rejeitar (Verificador, RF24) |
| aguardando_documentos | em_verificacao | Responder (Despachante, RF22) |
| rejeitado | em_verificacao | Corrigir e reenviar (Despachante, RN05 ≤3×) |
| aprovado_verificador | aprovado_final | Aprovação final (Chefe, RF25) |
| aprovado_verificador | em_verificacao | Devolver ao verificador (Chefe) |
| aprovado_final | em_verificacao | Reabrir (Admin, RN07, com justificativa) |

Cada transição **deve**: (1) validar ator/permissão, (2) validar pré-condições (RN),
(3) gravar `historico_tramitacao`, (4) gravar `logs_auditoria`, (5) disparar notificações (§11),
(6) atualizar timestamps (`data_submissao`, `data_distribuicao`, etc.).

---

## 10. SLA e prazos (RF26 / RN11)

`SLAService` calcula, para o estado atual, `horas_decorridas` desde o timestamp de entrada no
estado e compara com o limite (`configuracoes`):

- `< 75%` do limite → **verde**
- `75%–100%` → **amarelo**
- `> 100%` → **vermelho** + notificar Chefe (RN15) e marcar alerta.

Limites por fase: distribuição=`sla_distribuicao_horas`, verificação=`sla_verificacao_horas`,
aprovação chefe=`sla_aprovacao_chefe_horas`. Job agendado (cron) reavalia SLAs periodicamente
e gera notificações de estouro.

---

## 11. Notificações (RF29/RF30 / RN15)

Cada evento gera notificação in-app (`notificacoes`) e, se `email_notificacoes=true`, email
(PHPMailer). Matriz evento → destinatário:

| Evento | Notifica |
|---|---|
| Processo distribuído | Despachante + Verificador |
| Solicitação de documentos | Despachante |
| Despachante responde | Verificador |
| Aprovado pelo verificador | Chefe + Despachante |
| Aprovação final | Despachante + Verificador |
| Rejeição | Despachante |
| SLA ultrapassado | Chefe |
| Nova mensagem | A outra parte do processo |

---

## 12. Configuração do sistema

Parâmetros editáveis pelo Administrador (tabela `configuracoes`, RF44): prazos de SLA, tamanho
máximo de arquivo, ligar/desligar emails, modo de manutenção. Ler sempre da BD (com cache curto),
nunca hardcoded.

---

## 13. Infraestrutura e deploy

- **DocumentRoot:** `public/` apenas.
- **Apache 2.4** com `mod_rewrite` e `mod_headers`. VirtualHosts 80→443 (redirect 301),
  443 com SSL (`SSLCertificateFile`/`SSLCertificateKeyFile`), e bloqueio de
  `public/uploads/` (`Require all denied`) — ficheiros servidos por controlador autenticado.
- **`public/.htaccess`:** rewrite para `index.php?url=$1`; bloquear ficheiros dotfiles;
  `Options -Indexes`; cabeçalhos `X-Frame-Options SAMEORIGIN`, `X-XSS-Protection 1; mode=block`,
  `X-Content-Type-Options nosniff` (transcrever da monografia).
- **Backups:** BD + `uploads/` diários, off-site (RNF15).

---

## 14. Interfaces (obrigatório reproduzir)

O layout visual está definido no protótipo da monografia e **deve ser mantido**: tema azul
institucional da DGA, componentes Bootstrap, badges de estado coloridos, sidebar de navegação.
Existe uma implementação de referência do frontend (protótipo interativo já entregue) que deve
ser seguida pixel a pixel para estas três telas:

1. **Login (Figura 5):** cartão centrado sobre fundo azul; logótipo/ícone SAGDOC; título
   “SAGDOC — Sistema de Apoio à Gestão Documental”; campos Utilizador e Senha; “Manter sessão”;
   botão **Entrar**; link “Recuperar senha”.
2. **Dashboard do Despachante (Figura 6):** topbar + sidebar; 4 cartões de estatística (Total,
   Em verificação, Aprovados, Aguardando ação); botão **Novo Processo**; tabela “Meus processos
   recentes” com nº DU, importador, data de submissão, status (badge), verificador e ações.
3. **Formulário de Criação de Processo (Figura 7):** breadcrumb; campos nº DU, importador,
   categoria, regime, observações; painel lateral com **checklist dinâmico** (obrigatório/opcional);
   botão “Criar e avançar para documentos”.

Badges de estado (cores): rascunho (cinza), submetido/aguardando_distribuicao (azul),
em_verificacao/aguardando_documentos (amarelo/âmbar), aprovado_verificador/aprovado_final (verde),
rejeitado (vermelho), cancelado (cinza).

Telas adicionais a construir com o mesmo padrão: fila do verificador, detalhe do processo (dados +
documentos + timeline + comunicações + ações), distribuição, aprovação final, consulta/pesquisa,
dashboards por perfil, relatórios, e ecrãs de administração.

---

## 15. Endpoints (rotas sugeridas)

Roteamento via `index.php?url=...`. Todas exigem sessão exceto login/recuperação. Aplicar RBAC.

```
GET  /login                         POST /login
POST /logout                        GET  /recuperar-senha   POST /recuperar-senha
GET  /dashboard                     (resolve por perfil)

GET  /processos                     (lista filtrada por RN14)
GET  /processos/novo                POST /processos            (criar — RF06)
GET  /processos/{id}                (detalhe — RF33)
POST /processos/{id}/documentos     (upload — RF09)
DELETE /processos/{id}/documentos/{docId}   (rascunho — RF12)
POST /processos/{id}/submeter       (RF13)
POST /processos/{id}/cancelar
POST /processos/{id}/distribuir     (Chefe/auto — RF15/16)
POST /processos/{id}/aprovar        (Verificador — RF23)
POST /processos/{id}/solicitar-docs (RF20)
POST /processos/{id}/responder      (Despachante — RF22)
POST /processos/{id}/rejeitar       (RF24)
POST /processos/{id}/aprovar-final  (Chefe — RF25)
POST /processos/{id}/devolver
POST /processos/{id}/reabrir        (Admin — RN07)
GET  /processos/{id}/dossie.zip     (RF34)
GET  /documentos/{id}               (download autenticado)

GET/POST /processos/{id}/mensagens  (RF27)
GET  /notificacoes    POST /notificacoes/{id}/ler

GET  /relatorios      GET /relatorios/export?tipo=...&formato=pdf|excel   (RF40)

# Admin
GET/POST /admin/utilizadores        GET/POST /admin/tipos-documentos
GET/POST /admin/categorias          GET/POST /admin/sla
GET  /admin/logs                    POST /admin/backup
```

APIs auxiliares (JSON): validar DU, gerar checklist por categoria, progresso de upload.

---

## 16. Segurança — checklist de implementação

- [ ] PDO **prepared statements** em 100% das queries (RNF13/anti-SQLi).
- [ ] Escape de todo o output em Views (anti-XSS).
- [ ] Token **CSRF** em todos os POST; validado no middleware.
- [ ] **Rate limiting** no login (ex.: 5 tentativas / 15 min por IP+utilizador).
- [ ] Senhas com `password_hash()` (argon2id/bcrypt); `password_verify()`.
- [ ] **2FA** opcional (TOTP) para perfis administrativos (RF02).
- [ ] Uploads: validar MIME real (`finfo`), extensão, tamanho ≤10MB; nome sanitizado; guardar
      fora do webroot ou negar acesso direto; gravar `hash_sha256` (RNF26).
- [ ] AES-256 para campos sensíveis (RNF10) com `APP_KEY`.
- [ ] HTTPS forçado; cabeçalhos de segurança (§13).
- [ ] `logs_auditoria` sem UPDATE/DELETE pela app (RN12).
- [ ] Sessões com timeout por inatividade; regenerar id no login.

---

## 17. Dados de seed (desenvolvimento)

Criar `database/seed.sql` com: 1 admin, 1 gestor, 1 chefe, 2 verificadores, 2 despachantes,
4 importadores e ~8 processos cobrindo **todos** os estados (rascunho → aprovado_final,
rejeitado, aguardando_documentos, etc.), com documentos anexados e histórico coerente. Senha
demo `demo` (apenas dev). Utilizadores: `jbarbosa`, `mcande`, `averificador`, `nverificador`,
`chefe`, `gestor`, `admin`.

---

## 18. Testes (RNF19/20)

- **Unitários (PHPUnit):** validação de DU (RF07), geração de checklist (RN02/RF08),
  transições de estado válidas/inválidas (§9), cálculo de SLA (§10), matriz RBAC (§8),
  regra de propriedade RN14, limite de 3 reenvios (RN05).
- **Integração:** fluxo completo criar → submeter → distribuir → verificar → aprovar → final.
- **Segurança:** tentativas de SQLi/XSS/CSRF, rate limiting, upload de ficheiro inválido.

---

## 19. Ordem de implementação sugerida

1. Scaffolding: estrutura de pastas, Composer, `.env`, Core (Router/Database/Session/View), `.htaccess`.
2. `database/schema.sql` (tabelas, enums, índices, procedures, triggers, views) + `seed.sql`.
3. Autenticação + RBAC + auditoria de acesso (RF01–RF05, §8, §16).
4. Layout base (topbar, sidebar, tema) e as 3 telas de referência (§14).
5. Criação de processo + checklist + upload (RF06–RF12, RN01–RN03).
6. Submissão + recibo + máquina de estados + distribuição (RF13–RF17, §9).
7. Verificação: fila, visualizador, aprovar/rejeitar/solicitar (RF18–RF24).
8. Aprovação final + SLA + notificações (RF25/26, §10, §11).
9. Comunicações, consulta/pesquisa, detalhe, timeline, download (RF27–RF35).
10. Dashboards, relatórios e KPIs (RF36–RF41).
11. Administração: utilizadores, tipos doc, SLA, logs, backup (RF42–RF47).
12. Testes, hardening de segurança, deploy Apache/HTTPS (§13, §16, §18).

---

## 20. Definição de pronto (Definition of Done)

Uma funcionalidade está pronta quando: cumpre o RF/RN correspondente; respeita a matriz RBAC e
a regra de propriedade (RN14); grava histórico e auditoria; dispara as notificações previstas;
está coberta por testes; é responsiva e acessível (RNF06/08); e não introduz regressões de
segurança (§16).
