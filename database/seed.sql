-- SAGDOC — Dados de demonstração (SPECIFICATION.md §17)
-- Senha de todos os utilizadores: "demo" (APENAS desenvolvimento).
USE sagdoc;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE logs_auditoria;
TRUNCATE TABLE login_attempts;
TRUNCATE TABLE notificacoes;
TRUNCATE TABLE comunicacoes;
TRUNCATE TABLE historico_tramitacao;
TRUNCATE TABLE documentos;
TRUNCATE TABLE processos_documentais;
TRUNCATE TABLE importadores;
TRUNCATE TABLE despachantes;
TRUNCATE TABLE verificadores;
TRUNCATE TABLE usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- Hash de "demo" (bcrypt) — gerado com password_hash('demo', PASSWORD_BCRYPT)
SET @hash = '$2y$10$.IXnLM/YD3RKFmCGXInIr.ohpQ0T7OrgGKXJqY80eKla4CMs3CKwG';

-- ---------------------------------------------------------------------------
-- Utilizadores (ids 1-7, na ordem de inserção)
-- ---------------------------------------------------------------------------
INSERT INTO usuarios (username, password_hash, nome, email, telefone, perfil, ativo, data_criacao) VALUES
('jbarbosa', @hash, 'João Barbosa', 'jbarbosa@dga.gw', '+245 955 100 001', 'despachante', 1, NOW() - INTERVAL 400 DAY),
('mcande', @hash, 'Mariama Candé', 'mcande@dga.gw', '+245 955 100 002', 'despachante', 1, NOW() - INTERVAL 350 DAY),
('averificador', @hash, 'Aliu Baldé', 'abalde@dga.gw', '+245 955 100 003', 'verificador', 1, NOW() - INTERVAL 500 DAY),
('nverificador', @hash, 'Nália Gomes', 'ngomes@dga.gw', '+245 955 100 004', 'verificador', 1, NOW() - INTERVAL 480 DAY),
('chefe', @hash, 'Domingos Có', 'dco@dga.gw', '+245 955 100 005', 'chefe_setor', 1, NOW() - INTERVAL 600 DAY),
('gestor', @hash, 'Fatumata Djaló', 'fdjalo@dga.gw', '+245 955 100 006', 'gestor', 1, NOW() - INTERVAL 600 DAY),
('admin', @hash, 'Administrador do Sistema', 'admin@dga.gw', '+245 955 100 000', 'administrador', 1, NOW() - INTERVAL 700 DAY);

INSERT INTO despachantes (usuario_id, nif, numero_licenca, data_validade_licenca) VALUES
(1, '512334789', 'DESP-2019-042', DATE_ADD(CURDATE(), INTERVAL 6 MONTH)),
(2, '598112004', 'DESP-2021-118', DATE_ADD(CURDATE(), INTERVAL 10 MONTH));

INSERT INTO verificadores (usuario_id, matricula, setor) VALUES
(3, 'VER-204', 'Importação'),
(4, 'VER-207', 'Importação');

-- ---------------------------------------------------------------------------
-- Importadores (ids 1-4)
-- ---------------------------------------------------------------------------
INSERT INTO importadores (nome, nif, endereco, telefone, email) VALUES
('Bissau Trading Lda', '700112233', 'Av. Amílcar Cabral, Bissau', '+245 966 200 001', 'geral@bissautrading.gw'),
('Sahel Import & Export', '700445566', 'Bairro de Ajuda, Bissau', '+245 966 200 002', 'contacto@sahelie.gw'),
('Farmácia Central SA', '700778899', 'Praça dos Heróis, Bissau', '+245 966 200 003', 'compras@farmaciacentral.gw'),
('AgroCaju Guiné', '700990011', 'Bafatá, Guiné-Bissau', '+245 966 200 004', 'export@agrocaju.gw');

-- ---------------------------------------------------------------------------
-- Processos documentais (ids 1-8) — cobrem todos os estados (§17)
-- ---------------------------------------------------------------------------
INSERT INTO processos_documentais
    (numero_du, despachante_id, importador_id, categoria, regime, status, verificador_id, prioridade,
     tentativas_submissao, data_criacao, data_submissao, data_distribuicao,
     data_aprovacao_verificador, data_aprovacao_final, parecer_tecnico, motivo_rejeicao, observacoes)
VALUES
('2025/001234', 1, 1, 'Alimentos', 'Importação Definitiva', 'aprovado_final', 3, 'operador_confiavel', 0,
 NOW() - INTERVAL 200 HOUR, NOW() - INTERVAL 190 HOUR, NOW() - INTERVAL 188 HOUR,
 NOW() - INTERVAL 120 HOUR, NOW() - INTERVAL 100 HOUR,
 'Documentação conforme. Sem irregularidades.', NULL, 'Contentor 20ft, mercadoria perecível.'),

('2025/001240', 1, 2, 'Têxteis', 'Importação Definitiva', 'em_verificacao', 3, 'normal', 0,
 NOW() - INTERVAL 60 HOUR, NOW() - INTERVAL 50 HOUR, NOW() - INTERVAL 48 HOUR,
 NULL, NULL, NULL, NULL, NULL),

('2025/001255', 1, 3, 'Medicamentos', 'Importação Definitiva', 'aguardando_documentos', 3, 'normal', 0,
 NOW() - INTERVAL 500 HOUR, NOW() - INTERVAL 490 HOUR, NOW() - INTERVAL 488 HOUR,
 NULL, NULL, NULL, 'Falta Licença de Importação do Ministério da Saúde. Prazo: 5 dias úteis.', NULL),

('2025/001260', 2, 4, 'Vegetais', 'Exportação', 'aprovado_verificador', 4, 'normal', 0,
 NOW() - INTERVAL 90 HOUR, NOW() - INTERVAL 80 HOUR, NOW() - INTERVAL 78 HOUR,
 NOW() - INTERVAL 30 HOUR, NULL, 'Conforme, aguarda aprovação final do Chefe de Setor.', NULL, NULL),

('2025/001270', 2, 1, 'Electrónicos', 'Importação Definitiva', 'aguardando_distribuicao', NULL, 'normal', 0,
 NOW() - INTERVAL 10 HOUR, NOW() - INTERVAL 6 HOUR, NULL,
 NULL, NULL, NULL, NULL, NULL),

('2025/001281', 1, 2, 'Químicos', 'Entreposto Aduaneiro', 'rejeitado', 4, 'normal', 1,
 NOW() - INTERVAL 300 HOUR, NOW() - INTERVAL 290 HOUR, NOW() - INTERVAL 288 HOUR,
 NULL, NULL, NULL, 'Fatura comercial ilegível e Licença de Importação em falta; reenviar.', NULL),

('2025/001290', 2, 3, 'Geral', 'Trânsito', 'aprovado_final', 4, 'operador_confiavel', 0,
 NOW() - INTERVAL 250 HOUR, NOW() - INTERVAL 240 HOUR, NOW() - INTERVAL 238 HOUR,
 NOW() - INTERVAL 150 HOUR, NOW() - INTERVAL 130 HOUR,
 'Documentação completa e conforme.', NULL, NULL),

('2025/001299', 1, 4, 'Animais', 'Admissão Temporária', 'rascunho', NULL, 'normal', 0,
 NOW() - INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, NULL, NULL, 'Processo ainda em preparação.');

-- ---------------------------------------------------------------------------
-- Documentos (obrigatórios anexados conforme categoria — RN02)
-- Fatura=1, B/L=2, Packing=3, Sanitário=4, Fitossanitário=5, Licença=6, Origem=7
-- ---------------------------------------------------------------------------
INSERT INTO documentos (processo_id, tipo_documento_id, nome_arquivo, caminho_arquivo, tamanho_bytes, hash_sha256, data_upload, upload_por, verificado) VALUES
-- processo 1 (Alimentos: 1,2,3,4)
(1, 1, 'fatura_comercial_2025-001234.pdf', '1/seed_fatura_1.pdf', 482300, SHA2('seed-1-1', 256), NOW() - INTERVAL 199 HOUR, 1, 1),
(1, 2, 'conhecimento_embarque_2025-001234.pdf', '1/seed_bl_1.pdf', 355210, SHA2('seed-1-2', 256), NOW() - INTERVAL 199 HOUR, 1, 1),
(1, 3, 'packing_list_2025-001234.pdf', '1/seed_pl_1.pdf', 201040, SHA2('seed-1-3', 256), NOW() - INTERVAL 199 HOUR, 1, 1),
(1, 4, 'certificado_sanitario_2025-001234.pdf', '1/seed_cs_1.pdf', 158900, SHA2('seed-1-4', 256), NOW() - INTERVAL 199 HOUR, 1, 1),
-- processo 2 (Têxteis: 1,2,3)
(2, 1, 'fatura_comercial_2025-001240.pdf', '2/seed_fatura_2.pdf', 411200, SHA2('seed-2-1', 256), NOW() - INTERVAL 59 HOUR, 1, 0),
(2, 2, 'conhecimento_embarque_2025-001240.pdf', '2/seed_bl_2.pdf', 322100, SHA2('seed-2-2', 256), NOW() - INTERVAL 59 HOUR, 1, 0),
(2, 3, 'packing_list_2025-001240.pdf', '2/seed_pl_2.pdf', 190500, SHA2('seed-2-3', 256), NOW() - INTERVAL 59 HOUR, 1, 0),
-- processo 3 (Medicamentos: 1,2,3,6 — falta a Licença, por isso aguardando_documentos)
(3, 1, 'fatura_comercial_2025-001255.pdf', '3/seed_fatura_3.pdf', 398000, SHA2('seed-3-1', 256), NOW() - INTERVAL 499 HOUR, 1, 1),
(3, 2, 'conhecimento_embarque_2025-001255.pdf', '3/seed_bl_3.pdf', 287400, SHA2('seed-3-2', 256), NOW() - INTERVAL 499 HOUR, 1, 1),
(3, 3, 'packing_list_2025-001255.pdf', '3/seed_pl_3.pdf', 176300, SHA2('seed-3-3', 256), NOW() - INTERVAL 499 HOUR, 1, 1),
-- processo 4 (Vegetais: 1,2,3,5)
(4, 1, 'fatura_comercial_2025-001260.pdf', '4/seed_fatura_4.pdf', 302100, SHA2('seed-4-1', 256), NOW() - INTERVAL 89 HOUR, 2, 1),
(4, 2, 'conhecimento_embarque_2025-001260.pdf', '4/seed_bl_4.pdf', 275600, SHA2('seed-4-2', 256), NOW() - INTERVAL 89 HOUR, 2, 1),
(4, 3, 'packing_list_2025-001260.pdf', '4/seed_pl_4.pdf', 168400, SHA2('seed-4-3', 256), NOW() - INTERVAL 89 HOUR, 2, 1),
(4, 5, 'certificado_fitossanitario_2025-001260.pdf', '4/seed_cf_4.pdf', 142200, SHA2('seed-4-5', 256), NOW() - INTERVAL 89 HOUR, 2, 1),
-- processo 5 (Electrónicos: 1,2,3)
(5, 1, 'fatura_comercial_2025-001270.pdf', '5/seed_fatura_5.pdf', 355000, SHA2('seed-5-1', 256), NOW() - INTERVAL 9 HOUR, 2, 0),
(5, 2, 'conhecimento_embarque_2025-001270.pdf', '5/seed_bl_5.pdf', 298700, SHA2('seed-5-2', 256), NOW() - INTERVAL 9 HOUR, 2, 0),
(5, 3, 'packing_list_2025-001270.pdf', '5/seed_pl_5.pdf', 187600, SHA2('seed-5-3', 256), NOW() - INTERVAL 9 HOUR, 2, 0),
-- processo 6 (Químicos: 1,2,3,6 — rejeitado por fatura ilegível/licença em falta)
(6, 1, 'fatura_comercial_2025-001281.pdf', '6/seed_fatura_6.pdf', 512000, SHA2('seed-6-1', 256), NOW() - INTERVAL 299 HOUR, 1, 0),
(6, 2, 'conhecimento_embarque_2025-001281.pdf', '6/seed_bl_6.pdf', 267800, SHA2('seed-6-2', 256), NOW() - INTERVAL 299 HOUR, 1, 1),
(6, 3, 'packing_list_2025-001281.pdf', '6/seed_pl_6.pdf', 154300, SHA2('seed-6-3', 256), NOW() - INTERVAL 299 HOUR, 1, 1),
-- processo 7 (Geral: 1,2,3)
(7, 1, 'fatura_comercial_2025-001290.pdf', '7/seed_fatura_7.pdf', 344500, SHA2('seed-7-1', 256), NOW() - INTERVAL 249 HOUR, 2, 1),
(7, 2, 'conhecimento_embarque_2025-001290.pdf', '7/seed_bl_7.pdf', 298100, SHA2('seed-7-2', 256), NOW() - INTERVAL 249 HOUR, 2, 1),
(7, 3, 'packing_list_2025-001290.pdf', '7/seed_pl_7.pdf', 176900, SHA2('seed-7-3', 256), NOW() - INTERVAL 249 HOUR, 2, 1),
-- processo 8 (Animais: 1,2,3,4 — rascunho, apenas fatura anexada até agora)
(8, 1, 'fatura_comercial_2025-001299.pdf', '8/seed_fatura_8.pdf', 289000, SHA2('seed-8-1', 256), NOW() - INTERVAL 4 HOUR, 1, 0);

-- ---------------------------------------------------------------------------
-- Histórico de tramitação
-- ---------------------------------------------------------------------------
INSERT INTO historico_tramitacao (processo_id, usuario_id, data_hora, acao, status_anterior, status_novo, observacao) VALUES
(1, 1, NOW() - INTERVAL 200 HOUR, 'Processo criado (rascunho)', NULL, 'rascunho', NULL),
(1, 1, NOW() - INTERVAL 190 HOUR, 'Processo submetido para verificação', 'rascunho', 'aguardando_distribuicao', NULL),
(1, 5, NOW() - INTERVAL 188 HOUR, 'Distribuído a Aliu Baldé (automática)', 'aguardando_distribuicao', 'em_verificacao', NULL),
(1, 3, NOW() - INTERVAL 120 HOUR, 'Aprovado pelo verificador', 'em_verificacao', 'aprovado_verificador', 'Documentação conforme. Sem irregularidades.'),
(1, 5, NOW() - INTERVAL 100 HOUR, 'Aprovação final concedida', 'aprovado_verificador', 'aprovado_final', NULL),

(2, 1, NOW() - INTERVAL 60 HOUR, 'Processo criado (rascunho)', NULL, 'rascunho', NULL),
(2, 1, NOW() - INTERVAL 50 HOUR, 'Processo submetido para verificação', 'rascunho', 'aguardando_distribuicao', NULL),
(2, 5, NOW() - INTERVAL 48 HOUR, 'Distribuído a Aliu Baldé (automática)', 'aguardando_distribuicao', 'em_verificacao', NULL),

(3, 1, NOW() - INTERVAL 500 HOUR, 'Processo criado (rascunho)', NULL, 'rascunho', NULL),
(3, 1, NOW() - INTERVAL 490 HOUR, 'Processo submetido para verificação', 'rascunho', 'aguardando_distribuicao', NULL),
(3, 5, NOW() - INTERVAL 488 HOUR, 'Distribuído a Aliu Baldé (automática)', 'aguardando_distribuicao', 'em_verificacao', NULL),
(3, 3, NOW() - INTERVAL 400 HOUR, 'Solicitados documentos adicionais', 'em_verificacao', 'aguardando_documentos', 'Falta Licença de Importação do Ministério da Saúde. Prazo: 5 dias úteis.'),

(4, 2, NOW() - INTERVAL 90 HOUR, 'Processo criado (rascunho)', NULL, 'rascunho', NULL),
(4, 2, NOW() - INTERVAL 80 HOUR, 'Processo submetido para verificação', 'rascunho', 'aguardando_distribuicao', NULL),
(4, 5, NOW() - INTERVAL 78 HOUR, 'Distribuído a Nália Gomes (automática)', 'aguardando_distribuicao', 'em_verificacao', NULL),
(4, 4, NOW() - INTERVAL 30 HOUR, 'Aprovado pelo verificador', 'em_verificacao', 'aprovado_verificador', 'Conforme, aguarda aprovação final do Chefe de Setor.'),

(5, 2, NOW() - INTERVAL 10 HOUR, 'Processo criado (rascunho)', NULL, 'rascunho', NULL),
(5, 2, NOW() - INTERVAL 6 HOUR, 'Processo submetido para verificação', 'rascunho', 'aguardando_distribuicao', NULL),

(6, 1, NOW() - INTERVAL 300 HOUR, 'Processo criado (rascunho)', NULL, 'rascunho', NULL),
(6, 1, NOW() - INTERVAL 290 HOUR, 'Processo submetido para verificação', 'rascunho', 'aguardando_distribuicao', NULL),
(6, 5, NOW() - INTERVAL 288 HOUR, 'Distribuído a Nália Gomes (automática)', 'aguardando_distribuicao', 'em_verificacao', NULL),
(6, 4, NOW() - INTERVAL 200 HOUR, 'Processo rejeitado', 'em_verificacao', 'rejeitado', 'Fatura comercial ilegível e Licença de Importação em falta; reenviar.'),

(7, 2, NOW() - INTERVAL 250 HOUR, 'Processo criado (rascunho)', NULL, 'rascunho', NULL),
(7, 2, NOW() - INTERVAL 240 HOUR, 'Processo submetido para verificação', 'rascunho', 'aguardando_distribuicao', NULL),
(7, 5, NOW() - INTERVAL 238 HOUR, 'Distribuído a Nália Gomes (automática)', 'aguardando_distribuicao', 'em_verificacao', NULL),
(7, 4, NOW() - INTERVAL 150 HOUR, 'Aprovado pelo verificador', 'em_verificacao', 'aprovado_verificador', 'Documentação completa e conforme.'),
(7, 5, NOW() - INTERVAL 130 HOUR, 'Aprovação final concedida', 'aprovado_verificador', 'aprovado_final', NULL),

(8, 1, NOW() - INTERVAL 5 HOUR, 'Processo criado (rascunho)', NULL, 'rascunho', NULL);

-- ---------------------------------------------------------------------------
-- Comunicações (RF27) e notificações (RF30) — exemplo no processo 3
-- ---------------------------------------------------------------------------
INSERT INTO comunicacoes (processo_id, remetente_id, destinatario_id, data_hora, assunto, mensagem, lida) VALUES
(3, 3, 1, NOW() - INTERVAL 400 HOUR, 'Processo 2025/001255', 'Por favor anexe a Licença de Importação em falta.', 1),
(3, 1, 3, NOW() - INTERVAL 380 HOUR, 'Processo 2025/001255', 'Já solicitámos a licença ao Ministério da Saúde, aguardamos emissão.', 1);

INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link_referencia, data_hora, lida) VALUES
(1, 'solicitacao', 'Documentos adicionais solicitados', 'No processo 2025/001255: Licença de Importação em falta.', '/processos/3', NOW() - INTERVAL 400 HOUR, 0),
(1, 'aprovacao_final', 'Aprovação final concedida', 'O processo 2025/001234 foi aprovado.', '/processos/1', NOW() - INTERVAL 100 HOUR, 0),
(3, 'atribuicao', 'Novo processo na sua fila', 'O processo 2025/001240 foi-lhe atribuído para verificação.', '/processos/2', NOW() - INTERVAL 48 HOUR, 1),
(5, 'distribuicao', 'Processo aguarda distribuição', '2025/001270 pronto para distribuir.', '/processos/5', NOW() - INTERVAL 6 HOUR, 0),
(5, 'aprovacao', 'Processo aguarda aprovação final', '2025/001260 aprovado por Nália Gomes.', '/processos/4', NOW() - INTERVAL 30 HOUR, 0);
