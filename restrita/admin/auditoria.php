<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/auth.php';
$admin=exigirAdmin();
$rows=db()->query("SELECT a.*,u.nome usuario,u.email FROM auditoria a LEFT JOIN usuarios u ON u.id=a.usuario_id ORDER BY a.id DESC LIMIT 500")->fetchAll();
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Auditoria | Maurinsoft</title><link rel="stylesheet" href="../../assets/css/main.css"><style>.box{padding:1.4rem;border:1px solid var(--border-glass);border-radius:var(--radius-md);background:rgba(255,255,255,.03)}</style></head><body>
<header class="site-header"><div class="container nav-container"><a href="../../index.html" class="brand-logo"><img src="../../assets/img/logo-maurinsoft.svg" class="site-logo-img" alt="Maurinsoft"></a><nav class="nav-menu"><a class="nav-link" href="index.php">Visão Geral</a><a class="nav-link" href="usuarios.php">Usuários</a><a class="nav-link" href="planos.php">Planos</a><a class="nav-link" href="assinaturas.php">Assinaturas</a><a class="nav-link" href="servicos.php">Serviços</a><a class="nav-link" href="consumo.php">Consumo</a><a class="nav-link active" href="auditoria.php">Auditoria</a></nav><div class="nav-cta"><a class="btn btn-secondary btn-sm" href="../logout.php">Sair</a></div></div></header>
<main class="section"><div class="container"><div class="section-header" style="text-align:left"><span class="section-tag">Administração</span><h1 class="section-title">Auditoria</h1><p class="section-desc">Histórico das principais ações administrativas e autenticações.</p></div>
<section class="box"><div style="overflow-x:auto"><table class="specs-table"><thead><tr><th>Data</th><th>Usuário</th><th>Ação</th><th>Recurso</th><th>IP</th><th>Detalhes</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=e($r['criado_em'])?></td><td><?=e($r['usuario']??'Sistema')?></td><td><?=e($r['acao'])?></td><td><?=e($r['recurso'])?></td><td><?=e($r['ip'])?></td><td><code style="white-space:pre-wrap"><?=e($r['detalhe_json'])?></code></td></tr><?php endforeach; ?></tbody></table></div></section>
</div></main></body></html>
