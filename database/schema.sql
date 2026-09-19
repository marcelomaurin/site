CREATE DATABASE IF NOT EXISTS maurinsoft
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE maurinsoft;

CREATE TABLE planos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nome VARCHAR(100) NOT NULL,
  tipo ENUM('gratuito','pago') NOT NULL DEFAULT 'gratuito',
  descricao VARCHAR(255) NULL,
  preco DECIMAL(12,2) NULL,
  periodicidade ENUM('mensal','trimestral','semestral','anual','vitalicio') NOT NULL DEFAULT 'mensal',
  dias_teste INT UNSIGNED NOT NULL DEFAULT 0,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE usuarios (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  papel ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
  plano_id INT UNSIGNED NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  email_confirmado_em DATETIME NULL,
  ultimo_login DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuario_plano FOREIGN KEY (plano_id) REFERENCES planos(id)
) ENGINE=InnoDB;

CREATE TABLE assinaturas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NOT NULL,
  plano_id INT UNSIGNED NOT NULL,
  status ENUM('teste','ativa','suspensa','cancelada','expirada') NOT NULL DEFAULT 'ativa',
  inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fim DATETIME NULL,
  renovacao_automatica TINYINT(1) NOT NULL DEFAULT 0,
  referencia_pagamento VARCHAR(190) NULL,
  observacao VARCHAR(255) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX ix_assinaturas_usuario_status (usuario_id, status),
  INDEX ix_assinaturas_fim (fim),
  CONSTRAINT fk_ass_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_ass_plano FOREIGN KEY (plano_id) REFERENCES planos(id)
) ENGINE=InnoDB;

CREATE TABLE servicos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(60) NOT NULL UNIQUE,
  nome VARCHAR(120) NOT NULL,
  descricao TEXT NULL,
  unidade_consumo VARCHAR(40) NOT NULL DEFAULT 'uso',
  url_acesso VARCHAR(255) NULL,
  icone VARCHAR(100) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE plano_servicos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  plano_id INT UNSIGNED NOT NULL,
  servico_id INT UNSIGNED NOT NULL,
  permitido TINYINT(1) NOT NULL DEFAULT 1,
  limite_quantidade DECIMAL(14,2) NULL,
  periodo ENUM('diario','semanal','mensal','anual','vitalicio') NOT NULL DEFAULT 'mensal',
  UNIQUE KEY uq_plano_servico (plano_id, servico_id),
  CONSTRAINT fk_ps_plano FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE CASCADE,
  CONSTRAINT fk_ps_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE usuario_servicos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NOT NULL,
  servico_id INT UNSIGNED NOT NULL,
  permitido TINYINT(1) NOT NULL DEFAULT 1,
  limite_quantidade DECIMAL(14,2) NULL,
  periodo ENUM('diario','semanal','mensal','anual','vitalicio') NOT NULL DEFAULT 'mensal',
  inicio DATETIME NULL,
  fim DATETIME NULL,
  observacao VARCHAR(255) NULL,
  UNIQUE KEY uq_usuario_servico (usuario_id, servico_id),
  CONSTRAINT fk_us_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_us_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE consumo_servicos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NOT NULL,
  servico_id INT UNSIGNED NOT NULL,
  quantidade DECIMAL(14,2) NOT NULL DEFAULT 1,
  referencia VARCHAR(190) NULL,
  metadados_json JSON NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_consumo_usuario_servico_data (usuario_id, servico_id, criado_em),
  CONSTRAINT fk_cs_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_cs_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE auditoria (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NULL,
  acao VARCHAR(100) NOT NULL,
  recurso VARCHAR(100) NULL,
  detalhe_json JSON NULL,
  ip VARCHAR(45) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_auditoria_data (criado_em),
  CONSTRAINT fk_aud_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO planos (codigo, nome, tipo, descricao, preco, periodicidade) VALUES
('FREE', 'Gratuito', 'gratuito', 'Acesso gratuito com franquias limitadas.', 0.00, 'mensal'),
('PRO', 'Profissional', 'pago', 'Plano profissional com acesso ampliado.', NULL, 'mensal');

INSERT INTO servicos (codigo, nome, descricao, unidade_consumo, url_acesso) VALUES
('CHATBOT_IA', 'Chatbot & IA', 'Consultas ao assistente de IA.', 'consulta', NULL),
('GESTAO_FILAS', 'Gestão de Filas', 'Recursos de gestão e monitoramento de filas.', 'unidade', '/produtos/gestao-filas.html'),
('VIDEO_CHAMADA', 'Vídeo Chamada', 'Salas e sessões de videoconferência.', 'minuto', '/produtos/video-chamada.html'),
('GESTAO_VISTA', 'Gestão à Vista', 'Painéis e indicadores gerenciais.', 'painel', '/produtos/gestao-a-vista.html'),
('HEMACIAS', 'Contagem de Hemácias', 'Processamentos de imagens e contagens.', 'processamento', '/produtos/contagem-hemacias.html'),
('DOWNLOAD_TECNICO', 'Downloads Técnicos', 'Downloads de manuais, firmwares e pacotes.', 'download', NULL);

INSERT INTO plano_servicos (plano_id, servico_id, permitido, limite_quantidade, periodo)
SELECT p.id, s.id, 1,
  CASE s.codigo
    WHEN 'CHATBOT_IA' THEN 20
    WHEN 'VIDEO_CHAMADA' THEN 60
    WHEN 'DOWNLOAD_TECNICO' THEN 5
    ELSE 1
  END,
  'mensal'
FROM planos p
JOIN servicos s
WHERE p.codigo = 'FREE'
  AND s.codigo IN ('CHATBOT_IA','VIDEO_CHAMADA','DOWNLOAD_TECNICO');

INSERT INTO plano_servicos (plano_id, servico_id, permitido, limite_quantidade, periodo)
SELECT p.id, s.id, 1, NULL, 'mensal'
FROM planos p
JOIN servicos s
WHERE p.codigo = 'PRO';

-- Para atualizar uma instalação criada com uma versão anterior do schema,
-- use database/migrate_20260919.sql antes de utilizar os novos módulos.

-- Crie o primeiro administrador com password_hash() pelo PHP.
-- Exemplo:
-- INSERT INTO usuarios(nome,email,senha_hash,papel,plano_id)
-- VALUES ('Administrador','admin@exemplo.com','$2y$...', 'admin', NULL);
