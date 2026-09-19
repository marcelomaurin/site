<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/auth.php';
$u = exigirAdmin();

$totUsuarios = (int)db()->query("SELECT COUNT(*) FROM usuarios WHERE ativo=1")->fetchColumn();
$totServicos = (int)db()->query("SELECT COUNT(*) FROM servicos WHERE ativo=1")->fetchColumn();
$consumoMes = (float)db()->query("SELECT COALESCE(SUM(quantidade),0) FROM consumo_servicos WHERE criado_em>=DATE_FORMAT(NOW(),'%Y-%m-01')")->fetchColumn();
$ultimos = db()->query("SELECT c.criado_em,u.nome usuario,s.nome servico,c.quantidade,c.referencia
FROM consumo_servicos c
JOIN usuarios u ON u.id=c.usuario_id
JOIN servicos s ON s.id=c.servico_id
ORDER BY c.id DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Administração | Maurinsoft</title>
<link rel="stylesheet" href="../../assets/css/main.css">
</head>
<body>
<header class="site-header"><div class="container nav-container">
<a href="../../index.html" class="brand-logo"><img src="../../assets/img/logo-maurinsoft.svg" class="site-logo-img" alt="Maurinsoft"></a>
<nav class="nav-menu">
  <a class="nav-link active" href="index.php">Visão Geral</a>
  <a class="nav-link" href="servicos.php">Serviços e Limites</a>
</nav>
<div class="nav-cta"><span><?=e($u['nome'])?></span><a class="btn btn-secondary btn-sm" href="../logout.php">Sair</a></div>
</div></header>
<main class="section"><div class="container">
<div class="section-header" style="text-align:left">
<span class="section-tag">Administração</span>
<h1 class="section-title">Painel gerencial</h1>
<p class="section-desc">Visão administrativa de usuários, catálogo, concessões e consumo dos serviços.</p>
</div>

<div class="stats-banner" style="grid-template-columns:repeat(auto-fit,minmax(190px,1fr));margin-bottom:2rem">
<div class="stat-box"><span class="stat-number"><?=$totUsuarios?></span><span class="stat-title">Usuários ativos</span></div>
<div class="stat-box"><span class="stat-number"><?=$totServicos?></span><span class="stat-title">Serviços ativos</span></div>
<div class="stat-box"><span class="stat-number"><?=e((string)$consumoMes)?></span><span class="stat-title">Unidades consumidas no mês</span></div>
</div>

<div class="specs-card" style="padding:1.5rem">
<div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;flex-wrap:wrap">
<h2 style="color:#fff">Consumo recente</h2>
<a class="btn btn-primary btn-sm" href="servicos.php">Gerenciar serviços, planos e concessões</a>
</div>
<div style="overflow-x:auto">
<table class="specs-table">
<thead><tr><th>Data</th><th>Usuário</th><th>Serviço</th><th>Quantidade</th><th>Referência</th></tr></thead>
<tbody>
<?php foreach($ultimos as $r): ?>
<tr><td><?=e($r['criado_em'])?></td><td><?=e($r['usuario'])?></td><td><?=e($r['servico'])?></td><td><?=e((string)$r['quantidade'])?></td><td><?=e($r['referencia'])?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div></main>
</body></html>
