# SAGDOC — Sistema de Apoio à Gestão Documental Aduaneira

> Complemento ao **SYDONIA (ASYCUDA)** para digitalização, centralização e rastreamento
> da documentação dos processos de despacho aduaneiro na **Guiné-Bissau**.
> Direcção-Geral das Alfândegas (DGA).

O SAGDOC não substitui o SYDONIA (que continua a processar o cálculo de direitos e a DU).
O SAGDOC gere o **dossiê documental** que acompanha cada Declaração Única (DU): recebe os
documentos do despachante, encaminha-os para verificação, controla prazos (SLA), regista
todo o histórico e disponibiliza indicadores de gestão.

---

## Índice

- [Visão geral](#visão-geral)
- [Perfis de utilizador](#perfis-de-utilizador)
- [Ciclo de vida de um processo](#ciclo-de-vida-de-um-processo)
- [Stack tecnológico](#stack-tecnológico)
- [Estrutura de pastas](#estrutura-de-pastas)
- [Pré-requisitos](#pré-requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Executar em desenvolvimento](#executar-em-desenvolvimento)
- [Contas de demonstração (seed)](#contas-de-demonstração-seed)
- [Deploy em produção](#deploy-em-produção)
- [Segurança](#segurança)
- [Documentação de referência](#documentação-de-referência)

---

## Visão geral

O problema: nas alfândegas guineenses a tramitação de documentos é essencialmente física e
em papel, o que gera atrasos, extravios e dificuldade de rastreamento e auditoria. O SAGDOC
digitaliza esse fluxo, oferecendo:

- **Criação de processos** ligados ao nº de DU do SYDONIA, com **checklist dinâmico** de
  documentos por categoria de mercadoria.
- **Upload de documentos** (PDF/JPG/PNG) com pré-visualização e recibo digital de submissão.
- **Workflow de tramitação** com distribuição (automática/manual), verificação, solicitação
  de documentos adicionais, aprovação técnica e aprovação final.
- **Controlo de SLA** com alertas visuais (verde/amarelo/vermelho).
- **Comunicação interna** por processo e **notificações** (in-app + email).
- **Consulta/pesquisa**, **timeline de histórico** e **download do dossiê** (ZIP).
- **Dashboards e relatórios** por perfil, com KPIs.
- **Administração** (utilizadores, tipos de documento, SLA) e **auditoria imutável**.

## Perfis de utilizador

| Perfil | Descrição | Vê |
|---|---|---|
| **Despachante** | Cria processos e anexa documentos | Apenas os processos que criou |
| **Verificador Aduaneiro** | Analisa documentos, aprova/rejeita, pede documentos | Processos que lhe foram atribuídos |
| **Chefe de Setor** | Distribui processos e dá aprovação final | Todos os processos do seu setor |
| **Gestor DGA** | Consulta e relatórios gerenciais | Todos os processos |
| **Administrador** | Gere utilizadores, tipos de documento, SLA e auditoria | Tudo + configuração |
| **Consultor (read-only)** | Acesso de leitura para auditoria | Conforme âmbito atribuído |

O acesso é controlado por **RBAC (Role-Based Access Control)** — ver matriz de permissões na
[especificação](SPECIFICATION.md#8-matriz-de-permissões-rbac).

## Ciclo de vida de um processo

```
rascunho ─submeter─▶ aguardando_distribuicao ─distribuir─▶ em_verificacao
                                                              │
                        ┌─────────────────────────────────────┼──────────────────┐
                        ▼                     ▼                ▼                  ▼
             aguardando_documentos       rejeitado     aprovado_verificador   (SLA excedido → alerta)
                        │                     │                │
                     (responder)          (corrigir)      aprovação final
                        │                     │                │
                        └────────▶ em_verificacao ◀───────────┘ ▼
                                                          aprovado_final ✔ (irreversível)
```

## Stack tecnológico

| Camada | Tecnologia |
|---|---|
| Frontend | HTML5, CSS3, **Bootstrap 5.3**, JavaScript ES6+ |
| Bibliotecas JS | Chart.js 4.x, PDF.js, DataTables, SweetAlert2 |
| Backend | **PHP 8.2** (arquitetura MVC própria) |
| Dependências PHP | Composer, PHPMailer, firebase/php-jwt, Monolog |
| Base de dados | **MySQL 8.0** (InnoDB) |
| Servidor web | **Apache 2.4** (mod_rewrite, HTTPS) |
| Versionamento | Git |

## Estrutura de pastas

```
sagdoc/
├── public/                 # DocumentRoot (único diretório exposto)
│   ├── index.php           # Front controller (roteador)
│   ├── .htaccess           # URL rewriting + cabeçalhos de segurança
│   ├── assets/             # css, js, img
│   └── uploads/            # documentos (acesso negado via Apache)
├── app/
│   ├── Controllers/        # AuthController, ProcessoController, ...
│   ├── Models/             # Usuario, Processo, Documento, ...
│   ├── Views/              # templates PHP (login, dashboard, processo, ...)
│   ├── Services/           # WorkflowService, SLAService, NotificationService, ...
│   ├── Middleware/         # Auth, RBAC, CSRF, RateLimit
│   ├── Core/               # Router, Database, Request, Response, Session, View
│   └── Helpers/            # validação, formatação
├── config/                 # app.php, database.php, mail.php
├── database/
│   ├── schema.sql          # criação de tabelas, procedures, triggers, views
│   └── seed.sql            # dados de demonstração
├── storage/                # logs (Monolog), backups, temp
├── tests/                  # PHPUnit
├── composer.json
├── .env.example
├── README.md
└── SPECIFICATION.md
```

## Pré-requisitos

- PHP **8.2+** com extensões: `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip`, `openssl`
- MySQL **8.0+**
- Composer **2.x**
- Apache **2.4** com `mod_rewrite` e `mod_headers` (ou Nginx equivalente)

## Instalação

```bash
git clone <repo-url> sagdoc
cd sagdoc

# dependências PHP (usa o composer.phar local do projeto se não tiver o Composer instalado)
php composer.phar install

# configuração
cp .env.example .env
# editar .env com credenciais de BD e SMTP

# base de dados — usar SOURCE (não pipe) para preservar a codificação UTF-8 dos dados
mysql -u root -p -e "SOURCE database/schema.sql"
mysql -u root -p -e "SOURCE database/seed.sql"   # opcional (dados de demonstração)

# permissões de escrita
chmod -R 775 public/uploads storage
```

### Testes

```bash
php vendor/phpunit/phpunit/phpunit
```

Cobre: RF07 (formato do nº de DU), RN02/RF08 (checklist dinâmico), §9 (máquina de estados —
fluxo completo, RN03, RN05, RN06, RN07), §10 (semáforo de SLA), §8/RF03 (matriz RBAC) e RN14
(âmbito de acesso por perfil).

## Configuração

Todas as variáveis vivem em `.env` (nunca comitar). Principais chaves:

```dotenv
APP_ENV=local
APP_URL=https://sagdoc.gov.gw
APP_KEY=            # 32 bytes base64 — usado no cifrar AES-256

DB_HOST=127.0.0.1
DB_NAME=sagdoc
DB_USER=sagdoc
DB_PASS=

MAIL_HOST=smtp.gov.gw
MAIL_PORT=587
MAIL_USER=
MAIL_PASS=
MAIL_FROM=nao-responder@sagdoc.gov.gw

UPLOAD_MAX_MB=10
```

Parâmetros de negócio (SLA, e-mail on/off, modo manutenção) ficam na tabela `configuracoes`
e são editáveis pelo Administrador na interface — ver
[SPECIFICATION.md §12](SPECIFICATION.md#12-configuração-do-sistema).

## Executar em desenvolvimento

```bash
# servidor embutido do PHP, com router de desenvolvimento (recomendado)
php -S localhost:8000 -t public bin/dev_router.php
```

O router (`bin/dev_router.php`) só é necessário porque o servidor embutido do PHP não lê
`.htaccess`; sem ele, rotas cujo último segmento tem uma extensão reconhecida (ex.:
`/processos/{id}/dossie.zip`) seriam tratadas como pedido de ficheiro estático em vez de
passarem pelo front controller. Em produção, sob Apache + `.htaccess`, isto não é necessário —
`php -S localhost:8000 -t public` (sem router) também funciona para a generalidade das rotas.

Abrir `http://localhost:8000`.

> Nota de desenvolvimento: o servidor embutido do PHP também não lê `.htaccess`, pelo que o
> bloqueio de acesso direto a `public/uploads/` só é realmente aplicado sob Apache (produção).
> Em `php -S`, os ficheiros de `public/uploads/` ficam acessíveis diretamente — aceitável em
> ambiente local, nunca em produção.

## Contas de demonstração (seed)

Todas com a senha **`demo`** (apenas em ambiente de desenvolvimento; alterar em produção).

| Utilizador | Perfil |
|---|---|
| `jbarbosa` | Despachante |
| `averificador` | Verificador |
| `chefe` | Chefe de Setor |
| `gestor` | Gestor DGA |
| `admin` | Administrador |

## Deploy em produção

- Servir **apenas** `public/` como DocumentRoot.
- Forçar **HTTPS** (TLS 1.2+); redirecionar 80 → 443.
- Negar acesso direto a `public/uploads/` (servir ficheiros através de um controlador que
  valida permissões).
- `APP_ENV=production`, exibição de erros desligada, logs via Monolog em `storage/logs`.
- Backups **diários** da BD e dos uploads, em localização geograficamente separada.
- Ver a configuração Apache completa na
  [SPECIFICATION.md §13](SPECIFICATION.md#13-infraestrutura-e-deploy).

## Segurança

- Senhas com **bcrypt/argon2** + salt individual.
- **CSRF** em todos os formulários; **prepared statements** (anti-SQL-injection);
  escape de output (anti-XSS); **rate limiting** no login (anti-força-bruta).
- **2FA** opcional para perfis administrativos.
- Auditoria **imutável** (`logs_auditoria`) de todas as operações sensíveis.
- Documentos guardam **timestamp + hash SHA-256** para validade legal.

## Documentação de referência

- **[SPECIFICATION.md](SPECIFICATION.md)** — especificação técnica completa e implementável.
- Origem: monografia *"Sistema de Apoio à Gestão Documental dos Processos de Despacho
  Aduaneiro na Guiné-Bissau"*, Valdano Henrique Barbosa (2026), ESI/GHS — Engenharia
  Informática.

---

© 2026 DGA — Guiné-Bissau. Uso interno.
