-- SAGDOC — Esquema de base de dados (MySQL 8.0 / InnoDB / utf8mb4_unicode_ci)
-- Ver SPECIFICATION.md §3 para a definição funcional de cada tabela.

CREATE DATABASE IF NOT EXISTS sagdoc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sagdoc;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS logs_auditoria;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS notificacoes;
DROP TABLE IF EXISTS comunicacoes;
DROP TABLE IF EXISTS historico_tramitacao;
DROP TABLE IF EXISTS documentos;
DROP TABLE IF EXISTS processos_documentais;
DROP TABLE IF EXISTS tipos_documentos;
DROP TABLE IF EXISTS importadores;
DROP TABLE IF EXISTS verificadores;
DROP TABLE IF EXISTS despachantes;
DROP TABLE IF EXISTS configuracoes;
DROP TABLE IF EXISTS usuarios;

-- ---------------------------------------------------------------------------
-- 3.1 usuarios
-- ---------------------------------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefone VARCHAR(20) NULL,
    perfil ENUM('despachante','verificador','chefe_setor','gestor','administrador','consultor') NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    totp_secret VARCHAR(64) NULL,
    totp_ativo BOOLEAN NOT NULL DEFAULT FALSE,
    reset_token VARCHAR(64) NULL,
    reset_token_expira DATETIME NULL,
    data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL,
    INDEX idx_usuarios_perfil (perfil),
    INDEX idx_usuarios_ativo (ativo)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 3.2 despachantes (extends usuarios)
-- ---------------------------------------------------------------------------
CREATE TABLE despachantes (
    usuario_id INT PRIMARY KEY,
    nif VARCHAR(20) NOT NULL UNIQUE,
    numero_licenca VARCHAR(30) NOT NULL UNIQUE,
    data_validade_licenca DATE NULL,
    CONSTRAINT fk_despachantes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 3.3 verificadores (extends usuarios)
-- ---------------------------------------------------------------------------
CREATE TABLE verificadores (
    usuario_id INT PRIMARY KEY,
    matricula VARCHAR(20) NOT NULL UNIQUE,
    setor VARCHAR(50) NOT NULL,
    CONSTRAINT fk_verificadores_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 3.4 importadores
-- ---------------------------------------------------------------------------
CREATE TABLE importadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    nif VARCHAR(20) NOT NULL UNIQUE,
    endereco TEXT NULL,
    telefone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    data_cadastro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_importadores_nif (nif)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 3.6 tipos_documentos (criada antes de processos_documentais por FK futura)
-- ---------------------------------------------------------------------------
CREATE TABLE tipos_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NULL,
    formatos_aceites JSON NOT NULL,
    obrigatorio_para JSON NOT NULL,
    validade_meses INT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 3.5 processos_documentais
-- ---------------------------------------------------------------------------
CREATE TABLE processos_documentais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_du VARCHAR(20) NOT NULL UNIQUE,
    despachante_id INT NOT NULL,
    importador_id INT NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    regime VARCHAR(30) NOT NULL,
    status ENUM(
        'rascunho','submetido','aguardando_distribuicao','em_verificacao',
        'aguardando_documentos','aprovado_verificador','aprovado_final',
        'rejeitado','cancelado'
    ) NOT NULL DEFAULT 'rascunho',
    verificador_id INT NULL,
    prioridade ENUM('normal','operador_confiavel') NOT NULL DEFAULT 'normal',
    tentativas_submissao INT NOT NULL DEFAULT 0,
    data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_submissao DATETIME NULL,
    data_distribuicao DATETIME NULL,
    data_aprovacao_verificador DATETIME NULL,
    data_aprovacao_final DATETIME NULL,
    parecer_tecnico TEXT NULL,
    motivo_rejeicao TEXT NULL,
    observacoes TEXT NULL,
    CONSTRAINT fk_proc_despachante FOREIGN KEY (despachante_id) REFERENCES usuarios(id),
    CONSTRAINT fk_proc_importador FOREIGN KEY (importador_id) REFERENCES importadores(id),
    CONSTRAINT fk_proc_verificador FOREIGN KEY (verificador_id) REFERENCES usuarios(id),
    INDEX idx_proc_numero_du (numero_du),
    INDEX idx_proc_status (status),
    INDEX idx_proc_despachante (despachante_id),
    INDEX idx_proc_verificador (verificador_id),
    INDEX idx_proc_data_submissao (data_submissao)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 3.7 documentos
-- ---------------------------------------------------------------------------
CREATE TABLE documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    processo_id INT NOT NULL,
    tipo_documento_id INT NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    tamanho_bytes INT NOT NULL,
    hash_sha256 CHAR(64) NOT NULL,
    data_validade DATE NULL,
    data_upload TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    upload_por INT NOT NULL,
    verificado BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT fk_doc_processo FOREIGN KEY (processo_id) REFERENCES processos_documentais(id) ON DELETE CASCADE,
    CONSTRAINT fk_doc_tipo FOREIGN KEY (tipo_documento_id) REFERENCES tipos_documentos(id),
    CONSTRAINT fk_doc_upload_por FOREIGN KEY (upload_por) REFERENCES usuarios(id),
    INDEX idx_doc_processo (processo_id),
    INDEX idx_doc_tipo (tipo_documento_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 3.8 historico_tramitacao
-- ---------------------------------------------------------------------------
CREATE TABLE historico_tramitacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    processo_id INT NOT NULL,
    usuario_id INT NULL,
    data_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    acao VARCHAR(255) NOT NULL,
    status_anterior VARCHAR(30) NULL,
    status_novo VARCHAR(30) NULL,
    observacao TEXT NULL,
    CONSTRAINT fk_hist_processo FOREIGN KEY (processo_id) REFERENCES processos_documentais(id) ON DELETE CASCADE,
    CONSTRAINT fk_hist_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_hist_processo (processo_id),
    INDEX idx_hist_data (data_hora)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 3.9 comunicacoes
-- ---------------------------------------------------------------------------
CREATE TABLE comunicacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    processo_id INT NOT NULL,
    remetente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    data_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assunto VARCHAR(255) NULL,
    mensagem TEXT NOT NULL,
    lida BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT fk_com_processo FOREIGN KEY (processo_id) REFERENCES processos_documentais(id) ON DELETE CASCADE,
    CONSTRAINT fk_com_remetente FOREIGN KEY (remetente_id) REFERENCES usuarios(id),
    CONSTRAINT fk_com_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuarios(id),
    INDEX idx_com_processo (processo_id),
    INDEX idx_com_destinatario (destinatario_id),
    INDEX idx_com_lida (lida)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 3.10 notificacoes
-- ---------------------------------------------------------------------------
CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    mensagem TEXT NOT NULL,
    link_referencia VARCHAR(255) NULL,
    data_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lida BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_notif_usuario (usuario_id),
    INDEX idx_notif_lida (lida),
    INDEX idx_notif_data (data_hora)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 3.11 logs_auditoria (imutável — RN12). O utilizador de aplicação só deve
-- ter GRANT INSERT, SELECT nesta tabela (ver README §Segurança).
-- ---------------------------------------------------------------------------
CREATE TABLE logs_auditoria (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    data_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT NULL,
    acao VARCHAR(100) NOT NULL,
    entidade_afetada VARCHAR(50) NULL,
    id_entidade INT NULL,
    ip_origem VARCHAR(45) NULL,
    user_agent TEXT NULL,
    detalhes JSON NULL,
    CONSTRAINT fk_log_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_log_usuario (usuario_id),
    INDEX idx_log_data (data_hora),
    INDEX idx_log_acao (acao)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 3.12 configuracoes
-- ---------------------------------------------------------------------------
CREATE TABLE configuracoes (
    chave VARCHAR(50) PRIMARY KEY,
    valor TEXT NOT NULL,
    tipo ENUM('string','int','bool','json') NOT NULL DEFAULT 'string',
    descricao TEXT NULL,
    ultima_alteracao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Tabela de apoio (não listada no §3, necessária para RNF13/§16 rate limiting)
-- ---------------------------------------------------------------------------
CREATE TABLE login_attempts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    sucesso BOOLEAN NOT NULL,
    data_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_lookup (username, ip, sucesso, data_hora)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Dados iniciais — tipos_documentos (RN02)
-- ---------------------------------------------------------------------------
INSERT INTO tipos_documentos (nome, descricao, formatos_aceites, obrigatorio_para, validade_meses) VALUES
('Fatura Comercial', 'Fatura comercial emitida pelo exportador', '["pdf","jpg","png"]', '["*"]', NULL),
('Conhecimento de Embarque (B/L)', 'Bill of Lading / documento de transporte', '["pdf","jpg","png"]', '["*"]', NULL),
('Lista de Embalagem (Packing List)', 'Detalhe de volumes e conteúdo', '["pdf","jpg","png"]', '["*"]', NULL),
('Certificado Sanitário', 'Exigido para géneros alimentícios e animais', '["pdf","jpg","png"]', '["Alimentos","Animais"]', 6),
('Certificado Fitossanitário', 'Exigido para produtos vegetais', '["pdf","jpg","png"]', '["Vegetais"]', 6),
('Licença de Importação', 'Licença emitida pela entidade reguladora competente', '["pdf","jpg","png"]', '["Medicamentos","Químicos","Armas"]', 12),
('Certificado de Origem', 'Certifica o país de origem da mercadoria', '["pdf","jpg","png"]', '[]', NULL);

-- ---------------------------------------------------------------------------
-- Dados iniciais — configuracoes (RN11/§12)
-- ---------------------------------------------------------------------------
INSERT INTO configuracoes (chave, valor, tipo, descricao) VALUES
('sla_distribuicao_horas', '4', 'int', 'Prazo (horas) para distribuição de um processo submetido'),
('sla_verificacao_horas', '48', 'int', 'Prazo (horas) para verificação técnica'),
('sla_aprovacao_chefe_horas', '24', 'int', 'Prazo (horas) para aprovação final do Chefe de Setor'),
('tamanho_max_arquivo_mb', '10', 'int', 'Tamanho máximo por ficheiro em megabytes'),
('email_notificacoes', 'true', 'bool', 'Ativa o envio de notificações por email'),
('modo_manutencao', 'false', 'bool', 'Bloqueia o acesso a não administradores durante manutenção');

-- ---------------------------------------------------------------------------
-- Procedure — RF15: distribuição automática por balanceamento de carga
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS atribuir_verificador_automatico;
DROP TRIGGER IF EXISTS tr_processo_status_change;
DROP VIEW IF EXISTS v_processos_completos;
DROP VIEW IF EXISTS v_estatisticas_verificador;

DELIMITER $$

CREATE PROCEDURE atribuir_verificador_automatico(IN p_processo_id INT)
BEGIN
    DECLARE v_verificador_id INT;
    DECLARE v_setor VARCHAR(50);
    DECLARE v_numero_du VARCHAR(20);

    SELECT numero_du INTO v_numero_du FROM processos_documentais WHERE id = p_processo_id;

    SELECT v.usuario_id INTO v_verificador_id
    FROM verificadores v
    INNER JOIN usuarios u ON u.id = v.usuario_id AND u.ativo = TRUE
    LEFT JOIN processos_documentais p
        ON p.verificador_id = v.usuario_id
       AND p.status IN ('em_verificacao', 'aguardando_documentos')
    GROUP BY v.usuario_id
    ORDER BY COUNT(p.id) ASC, v.usuario_id ASC
    LIMIT 1;

    IF v_verificador_id IS NOT NULL THEN
        SET @sagdoc_skip_trigger = 1;

        UPDATE processos_documentais
           SET verificador_id = v_verificador_id,
               status = 'em_verificacao',
               data_distribuicao = NOW()
         WHERE id = p_processo_id;

        SET @sagdoc_skip_trigger = NULL;

        INSERT INTO historico_tramitacao (processo_id, usuario_id, acao, status_anterior, status_novo, observacao)
        VALUES (p_processo_id, v_verificador_id, 'Distribuído automaticamente (balanceamento de carga)',
                'aguardando_distribuicao', 'em_verificacao', CONCAT('Processo ', v_numero_du, ' atribuído automaticamente'));
    END IF;
END$$

DELIMITER ;

-- ---------------------------------------------------------------------------
-- Trigger — backstop de rastreabilidade (garante histórico mesmo em updates
-- diretos que não passem pelo WorkflowService). O WorkflowService grava o
-- histórico ele próprio (com ator/observação corretos) e define a variável de
-- sessão @sagdoc_skip_trigger antes do UPDATE para evitar registo duplicado;
-- qualquer alteração direta (fora da aplicação) não define essa variável e
-- fica sempre capturada por este trigger.
-- ---------------------------------------------------------------------------
DELIMITER $$

CREATE TRIGGER tr_processo_status_change
AFTER UPDATE ON processos_documentais
FOR EACH ROW
BEGIN
    IF NOT (OLD.status <=> NEW.status) AND @sagdoc_skip_trigger IS NULL THEN
        INSERT INTO historico_tramitacao (processo_id, usuario_id, acao, status_anterior, status_novo, observacao)
        VALUES (NEW.id, NEW.verificador_id, 'Alteração de estado', OLD.status, NEW.status, 'Registo automático (trigger — alteração direta)');
    END IF;
END$$

DELIMITER ;

-- ---------------------------------------------------------------------------
-- View — processo completo com tempo de tramitação (RF35/RF40)
-- ---------------------------------------------------------------------------
CREATE VIEW v_processos_completos AS
SELECT
    p.id,
    p.numero_du,
    p.categoria,
    p.regime,
    p.status,
    p.prioridade,
    p.data_criacao,
    p.data_submissao,
    p.data_distribuicao,
    p.data_aprovacao_verificador,
    p.data_aprovacao_final,
    desp.id AS despachante_id,
    desp.nome AS despachante_nome,
    imp.id AS importador_id,
    imp.nome AS importador_nome,
    imp.nif AS importador_nif,
    verif.id AS verificador_id,
    verif.nome AS verificador_nome,
    TIMESTAMPDIFF(HOUR, p.data_submissao, COALESCE(p.data_aprovacao_final, NOW())) AS horas_tramitacao
FROM processos_documentais p
INNER JOIN usuarios desp ON desp.id = p.despachante_id
INNER JOIN importadores imp ON imp.id = p.importador_id
LEFT JOIN usuarios verif ON verif.id = p.verificador_id;

-- ---------------------------------------------------------------------------
-- View — estatísticas por verificador (RF41)
-- ---------------------------------------------------------------------------
CREATE VIEW v_estatisticas_verificador AS
SELECT
    u.id AS verificador_id,
    u.nome AS verificador_nome,
    COUNT(p.id) AS total_processos,
    SUM(CASE WHEN p.status = 'em_verificacao' THEN 1 ELSE 0 END) AS em_verificacao,
    SUM(CASE WHEN p.status IN ('aprovado_verificador','aprovado_final') THEN 1 ELSE 0 END) AS aprovados,
    AVG(CASE
        WHEN p.data_aprovacao_verificador IS NOT NULL
        THEN TIMESTAMPDIFF(HOUR, COALESCE(p.data_distribuicao, p.data_submissao), p.data_aprovacao_verificador)
        ELSE NULL
    END) AS tempo_medio_horas
FROM usuarios u
INNER JOIN verificadores v ON v.usuario_id = u.id
LEFT JOIN processos_documentais p ON p.verificador_id = u.id
GROUP BY u.id, u.nome;
