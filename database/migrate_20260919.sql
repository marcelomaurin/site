USE maurinsoft;

ALTER TABLE planos
  ADD COLUMN descricao VARCHAR(255) NULL AFTER tipo,
  ADD COLUMN preco DECIMAL(12,2) NULL AFTER descricao,
  ADD COLUMN periodicidade ENUM('mensal','trimestral','semestral','anual','vitalicio') NOT NULL DEFAULT 'mensal' AFTER preco,
  ADD COLUMN dias_teste INT UNSIGNED NOT NULL DEFAULT 0 AFTER periodicidade,
  ADD COLUMN atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER criado_em;

ALTER TABLE usuarios
  ADD COLUMN email_confirmado_em DATETIME NULL AFTER ativo;

ALTER TABLE servicos
  ADD COLUMN url_acesso VARCHAR(255) NULL AFTER unidade_consumo,
  ADD COLUMN icone VARCHAR(100) NULL AFTER url_acesso;

CREATE TABLE IF NOT EXISTS assinaturas (
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

UPDATE planos SET descricao='Acesso gratuito com franquias limitadas.', preco=0.00 WHERE codigo='FREE';
UPDATE planos SET descricao='Plano profissional com acesso ampliado.' WHERE codigo='PRO';

UPDATE servicos SET url_acesso='/produtos/gestao-filas.html' WHERE codigo='GESTAO_FILAS';
UPDATE servicos SET url_acesso='/produtos/video-chamada.html' WHERE codigo='VIDEO_CHAMADA';
UPDATE servicos SET url_acesso='/produtos/gestao-a-vista.html' WHERE codigo='GESTAO_VISTA';
UPDATE servicos SET url_acesso='/produtos/contagem-hemacias.html' WHERE codigo='HEMACIAS';
