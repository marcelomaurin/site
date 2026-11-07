<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$acao = (string)($_POST['acao'] ?? '');
$msgSucesso = '';
$msgErro = '';

// Se o usuário solicitou inicialização das tabelas
if ($acao === 'inicializar_tabelas') {
    try {
        $pdo = db();
        
        $sql = "
        CREATE TABLE IF NOT EXISTS planos (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS usuarios (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          nome VARCHAR(150) NOT NULL,
          email VARCHAR(190) NOT NULL UNIQUE,
          google_sub VARCHAR(255) NULL,
          avatar_url VARCHAR(500) NULL,
          senha_hash VARCHAR(255) NOT NULL,
          papel ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
          plano_id INT UNSIGNED NULL,
          ativo TINYINT(1) NOT NULL DEFAULT 1,
          email_confirmado_em DATETIME NULL,
          ultimo_login DATETIME NULL,
          criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY uq_usuarios_google_sub (google_sub),
          INDEX ix_usuarios_plano (plano_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
          INDEX ix_assinaturas_fim (fim)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS servicos (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS plano_servicos (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          plano_id INT UNSIGNED NOT NULL,
          servico_id INT UNSIGNED NOT NULL,
          permitido TINYINT(1) NOT NULL DEFAULT 1,
          limite_quantidade DECIMAL(14,2) NULL,
          periodo ENUM('diario','semanal','mensal','anual','vitalicio') NOT NULL DEFAULT 'mensal',
          UNIQUE KEY uq_plano_servico (plano_id, servico_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS usuario_servicos (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          usuario_id BIGINT UNSIGNED NOT NULL,
          servico_id INT UNSIGNED NOT NULL,
          permitido TINYINT(1) NOT NULL DEFAULT 1,
          limite_quantidade DECIMAL(14,2) NULL,
          periodo ENUM('diario','semanal','mensal','anual','vitalicio') NOT NULL DEFAULT 'mensal',
          inicio DATETIME NULL,
          fim DATETIME NULL,
          observacao VARCHAR(255) NULL,
          UNIQUE KEY uq_usuario_servico (usuario_id, servico_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS consumo_servicos (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          usuario_id BIGINT UNSIGNED NOT NULL,
          servico_id INT UNSIGNED NOT NULL,
          quantidade DECIMAL(14,2) NOT NULL DEFAULT 1,
          referencia VARCHAR(190) NULL,
          metadados_json JSON NULL,
          criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX ix_consumo_usuario_servico_data (usuario_id, servico_id, criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS auditoria (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          usuario_id BIGINT UNSIGNED NULL,
          acao VARCHAR(100) NOT NULL,
          recurso VARCHAR(100) NULL,
          detalhe_json JSON NULL,
          ip VARCHAR(45) NULL,
          criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX ix_auditoria_data (criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        INSERT IGNORE INTO planos (codigo, nome, tipo, descricao, preco, periodicidade) VALUES
        ('FREE', 'Gratuito', 'gratuito', 'Acesso gratuito com franquias limitadas.', 0.00, 'mensal'),
        ('PRO', 'Profissional', 'pago', 'Plano profissional com acesso ampliado.', NULL, 'mensal');

        INSERT IGNORE INTO servicos (codigo, nome, descricao, unidade_consumo, url_acesso) VALUES
        ('CHATBOT_IA', 'Chatbot & IA', 'Consultas ao assistente de IA.', 'consulta', NULL),
        ('GESTAO_FILAS', 'Gestão de Filas', 'Recursos de gestão e monitoramento de filas.', 'unidade', '/produtos/gestao-filas.html'),
        ('VIDEO_CHAMADA', 'Vídeo Chamada', 'Salas e sessões de videoconferência.', 'minuto', '/produtos/video-chamada.html'),
        ('GESTAO_VISTA', 'Gestão à Vista', 'Painéis e indicadores gerenciais.', 'painel', '/produtos/gestao-a-vista.html'),
        ('HEMACIAS', 'Contagem de Hemácias', 'Processamentos de imagens e contagens.', 'processamento', '/produtos/contagem-hemacias.html'),
        ('DOWNLOAD_TECNICO', 'Downloads Técnicos', 'Downloads de manuais, firmwares e pacotes.', 'download', NULL);
        ";

        $pdo->exec($sql);
        $msgSucesso = 'Tabelas e registros padrão inicializados com sucesso!';
    } catch (Throwable $e) {
        $msgErro = 'Erro ao inicializar tabelas: ' . $e->getMessage();
    }
}

// Checagens de ambiente
$rootConfigExiste = is_file(dirname(__DIR__) . '/config.php');
$rootVariaveisExiste = is_file(dirname(__DIR__) . '/variaveis.php');
$localConfigExiste = is_file(__DIR__ . '/config.local.php');
$extPdo = extension_loaded('pdo');
$extPdoMysql = extension_loaded('pdo_mysql');
$phpVersao = PHP_VERSION;

$dbStatus = false;
$dbErro = '';
$tabelasExistentes = [];
$tabelasNecessarias = ['usuarios', 'planos', 'servicos', 'assinaturas', 'plano_servicos', 'usuario_servicos', 'consumo_servicos', 'auditoria'];
$adminExiste = false;
$totalUsuarios = 0;

if (dbConfigurado()) {
    try {
        $pdo = db();
        $dbStatus = true;
        
        $res = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $tabelasExistentes = array_map('strtolower', $res);
        
        if (in_array('usuarios', $tabelasExistentes, true)) {
            $totalUsuarios = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
            $adminExiste = (bool)$pdo->query("SELECT 1 FROM usuarios WHERE papel='admin' AND ativo=1 LIMIT 1")->fetchColumn();
        }
    } catch (Throwable $e) {
        $dbStatus = false;
        $dbErro = $e->getMessage();
    }
} else {
    $dbErro = 'Credenciais não configuradas (DB_PASS vazio ou ausente). Crie restrita/config.local.php.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Diagnóstico do Banco de Dados | Maurinsoft</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    .diag-wrapper { min-height: calc(100vh - 80px); padding: 3rem 1rem; }
    .diag-card { max-width: 760px; margin: 0 auto; background: rgba(13,20,36,.96); border: 1px solid var(--border-accent); border-radius: var(--radius-lg); padding: 2.5rem; box-shadow: var(--shadow-lg); }
    .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 0.8rem; font-weight: 700; }
    .status-badge.ok { background: rgba(16,185,129,.15); color: #34d399; border: 1px solid rgba(16,185,129,.3); }
    .status-badge.warn { background: rgba(245,158,11,.15); color: #fbbf24; border: 1px solid rgba(245,158,11,.3); }
    .status-badge.bad { background: rgba(239,68,68,.15); color: #f87171; border: 1px solid rgba(239,68,68,.3); }
    .diag-table { width: 100%; border-collapse: collapse; margin: 1.25rem 0; font-size: 0.9rem; }
    .diag-table th, .diag-table td { padding: 10px 14px; text-align: left; border-bottom: 1px solid rgba(255,255,255,.07); }
    .diag-table th { color: var(--text-dim); font-size: 0.8rem; text-transform: uppercase; }
    .code-box { background: rgba(0,0,0,.4); padding: 1rem; border-radius: 6px; font-family: monospace; font-size: 0.85rem; color: #38bdf8; overflow-x: auto; margin: 1rem 0; }
  </style>
</head>
<body>
<header class="site-header">
  <div class="container nav-container">
    <a href="../index.html" class="brand-logo"><img src="../assets/img/logo-maurinsoft.svg" alt="Maurinsoft" class="site-logo-img"></a>
    <a href="login.php" class="btn btn-secondary btn-sm">&larr; Voltar ao Login</a>
  </div>
</header>

<main class="diag-wrapper">
  <div class="diag-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
      <h1 style="color:#fff; font-size: 1.5rem; margin:0;">Diagnóstico do Banco de Dados</h1>
      <span class="status-badge <?= $dbStatus ? 'ok' : 'bad' ?>">
        <?= $dbStatus ? '● CONEXÃO ATIVA' : '● FALHA DE CONEXÃO' ?>
      </span>
    </div>

    <?php if ($msgSucesso): ?>
      <div style="padding:12px; margin-bottom:1rem; border-radius:6px; background:rgba(16,185,129,.15); border:1px solid #10b981; color:#6ee7b7; font-size:0.9rem;">
        ✅ <?=e($msgSucesso)?>
      </div>
    <?php endif; ?>

    <?php if ($msgErro): ?>
      <div style="padding:12px; margin-bottom:1rem; border-radius:6px; background:rgba(239,68,68,.15); border:1px solid #ef4444; color:#fca5a5; font-size:0.9rem;">
        ❌ <?=e($msgErro)?>
      </div>
    <?php endif; ?>

    <h2 style="color:var(--primary); font-size:1.1rem; margin-top:1.5rem;">1. Verificação de Ambiente</h2>
    <table class="diag-table">
      <tr>
        <td>PHP Version</td>
        <td><?=e($phpVersao)?></td>
        <td><span class="status-badge <?= version_compare($phpVersao, '8.0', '>=') ? 'ok' : 'bad' ?>"><?= version_compare($phpVersao, '8.0', '>=') ? 'OK' : 'Mínimo 8.0' ?></span></td>
      </tr>
      <tr>
        <td>Extensão PDO MySQL</td>
        <td><?= $extPdoMysql ? 'Habilitada' : 'Ausente' ?></td>
        <td><span class="status-badge <?= $extPdoMysql ? 'ok' : 'bad' ?>"><?= $extPdoMysql ? 'OK' : 'Erro' ?></span></td>
      </tr>
      <tr>
        <td>Arquivo config.php (Raiz)</td>
        <td><?= $rootConfigExiste ? 'Presente na raiz do site' : ($rootVariaveisExiste ? 'variaveis.php presente na raiz' : 'Ausente na raiz') ?></td>
        <td><span class="status-badge <?= ($rootConfigExiste || $rootVariaveisExiste) ? 'ok' : 'warn' ?>"><?= ($rootConfigExiste || $rootVariaveisExiste) ? 'OK' : 'Opcional' ?></span></td>
      </tr>
      <tr>
        <td>Arquivo config.local.php (Restrita)</td>
        <td><?= $localConfigExiste ? 'Presente no servidor' : 'Ausente (usando raiz/defaults)' ?></td>
        <td><span class="status-badge <?= $localConfigExiste ? 'ok' : 'warn' ?>"><?= $localConfigExiste ? 'OK' : 'Opcional' ?></span></td>
      </tr>
    </table>

    <h2 style="color:var(--primary); font-size:1.1rem; margin-top:1.5rem;">2. Parâmetros de Conexão</h2>
    <table class="diag-table">
      <tr>
        <td>Host / Porta</td>
        <td><?=e(defined('DB_HOST') ? DB_HOST : 'localhost')?>:<?=e(defined('DB_PORT') ? DB_PORT : '3306')?></td>
      </tr>
      <tr>
        <td>Banco de Dados (DB_NAME)</td>
        <td><strong><?=e(defined('DB_NAME') ? DB_NAME : 'u820932905_maurinsoftdb')?></strong></td>
      </tr>
      <tr>
        <td>Usuário (DB_USER)</td>
        <td><strong><?=e(defined('DB_USER') ? DB_USER : 'u820932905_mmm')?></strong></td>
      </tr>
      <tr>
        <td>Senha (DB_PASS)</td>
        <td>
          <?= dbConfigurado() ? '•••••••• (' . strlen(DB_PASS) . ' caracteres)' : '<span style="color:#f87171;">Não configurada ou vazia</span>' ?>
        </td>
      </tr>
      <tr>
        <td>Status da Conexão PDO</td>
        <td>
          <?php if ($dbStatus): ?>
            <span style="color:#34d399; font-weight:600;">Conectado com sucesso ao MySQL!</span>
          <?php else: ?>
            <span style="color:#f87171; font-weight:600;">Falha: <?=e($dbErro)?></span>
          <?php endif; ?>
        </td>
      </tr>
    </table>

    <h2 style="color:var(--primary); font-size:1.1rem; margin-top:1.5rem;">3. Verificação de Tabelas</h2>
    <?php if ($dbStatus): ?>
      <table class="diag-table">
        <thead>
          <tr>
            <th>Tabela</th>
            <th>Necessária</th>
            <th>Status no Banco</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $todasPresentes = true;
          foreach ($tabelasNecessarias as $tab): 
              $existe = in_array(strtolower($tab), $tabelasExistentes, true);
              if (!$existe) $todasPresentes = false;
          ?>
            <tr>
              <td><code><?=e($tab)?></code></td>
              <td>Sim</td>
              <td>
                <span class="status-badge <?= $existe ? 'ok' : 'bad' ?>">
                  <?= $existe ? 'PRESENTE' : 'AUSENTE' ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if (!$todasPresentes): ?>
        <form method="post" style="margin-top:1.5rem;">
          <input type="hidden" name="acao" value="inicializar_tabelas">
          <button class="btn btn-primary" type="submit">⚡ Inicializar Tabelas Ausentes Agora</button>
        </form>
      <?php else: ?>
        <div style="padding:12px; border-radius:6px; background:rgba(16,185,129,.1); color:#34d399; font-size:0.88rem;">
          Todas as tabelas do sistema estão criadas e prontas para uso. Total de usuários: <strong><?=$totalUsuarios?></strong>.
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div style="padding:14px; border-radius:6px; background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3); color:#fca5a5; font-size:0.9rem; line-height:1.6;">
        <strong>Como resolver a falha de conexão:</strong><br>
        1. No gerenciador de arquivos da Hostinger (ou via FTP/SSH), acesse a pasta <code>restrita/</code>.<br>
        2. Crie ou edite o arquivo <code>config.local.php</code> com suas credenciais do MySQL:<br>
        <div class="code-box">
&lt;?php<br>
declare(strict_types=1);<br>
<br>
const DB_HOST = 'localhost';<br>
const DB_PORT = '3306';<br>
const DB_NAME = 'u820932905_maurinsoftdb'; // Seu banco no painel da Hostinger<br>
const DB_USER = 'u820932905_mmm';          // Seu usuário MySQL no painel<br>
const DB_PASS = 'SuaSenhaAqui';             // Senha configurada para o usuário MySQL<br>
        </div>
        3. Recarregue esta página para validar o status.
      </div>
    <?php endif; ?>

    <div style="margin-top:2rem; text-align:center;">
      <a href="login.php" class="btn btn-secondary btn-sm">&larr; Voltar para a Tela de Login</a>
    </div>
  </div>
</main>
</body>
</html>
