<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/auth.php';
$admin=exigirAdmin();

$usuarioId=(int)($_GET['usuario_id']??0);
$servicoId=(int)($_GET['servico_id']??0);
$inicio=trim((string)($_GET['inicio']??date('Y-m-01')));
$fim=trim((string)($_GET['fim']??date('Y-m-d')));

$where=["c.criado_em >= ?","c.criado_em < DATE_ADD(?,INTERVAL 1 DAY)"];
$args=[$inicio.' 00:00:00',$fim.' 00:00:00'];
if($usuarioId){$where[]='c.usuario_id=?';$args[]=$usuarioId;}
if($servicoId){$where[]='c.servico_id=?';$args[]=$servicoId;}

$sql="SELECT c.*,u.nome usuario,u.email,s.nome servico,s.unidade_consumo
FROM consumo_servicos c
JOIN usuarios u ON u.id=c.usuario_id
JOIN servicos s ON s.id=c.servico_id
WHERE ".implode(' AND ',$where)." ORDER BY c.id DESC LIMIT 500";
$st=db()->prepare($sql);$st->execute($args);$rows=$st->fetchAll();

$total=0;foreach($rows as $r)$total+=(float)$r['quantidade'];
$usuarios=db()->query("SELECT id,nome FROM usuarios ORDER BY nome")->fetchAll();
$servicos=db()->query("SELECT id,nome FROM servicos ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Consumo | Maurinsoft</title><link rel="stylesheet" href="../../assets/css/main.css"><style>.box{padding:1.4rem;border:1px solid var(--border-glass);border-radius:var(--radius-md);background:rgba(255,255,255,.03)}.filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.7rem}</style></head><body>
<header class="site-header"><div class="container nav-container"><a href="../../index.html" class="brand-logo"><img src="../../assets/img/logo-maurinsoft.svg" class="site-logo-img" alt="Maurinsoft"></a><nav class="nav-menu"><a class="nav-link" href="index.php">Visão Geral</a><a class="nav-link" href="usuarios.php">Usuários</a><a class="nav-link" href="planos.php">Planos</a><a class="nav-link" href="assinaturas.php">Assinaturas</a><a class="nav-link" href="servicos.php">Serviços</a><a class="nav-link active" href="consumo.php">Consumo</a><a class="nav-link" href="auditoria.php">Auditoria</a></nav><div class="nav-cta"><a class="btn btn-secondary btn-sm" href="../logout.php">Sair</a></div></div></header>
<main class="section"><div class="container"><div class="section-header" style="text-align:left"><span class="section-tag">Administração</span><h1 class="section-title">Consumo dos serviços</h1><p class="section-desc">Acompanhe utilização por usuário, serviço e período.</p></div>
<section class="box"><form method="get" class="filters">
<select class="form-control" name="usuario_id"><option value="0">Todos os usuários</option><?php foreach($usuarios as $u): ?><option value="<?=$u['id']?>" <?=$usuarioId==$u['id']?'selected':''?>><?=e($u['nome'])?></option><?php endforeach; ?></select>
<select class="form-control" name="servico_id"><option value="0">Todos os serviços</option><?php foreach($servicos as $s): ?><option value="<?=$s['id']?>" <?=$servicoId==$s['id']?'selected':''?>><?=e($s['nome'])?></option><?php endforeach; ?></select>
<input class="form-control" type="date" name="inicio" value="<?=e($inicio)?>">
<input class="form-control" type="date" name="fim" value="<?=e($fim)?>">
<button class="btn btn-primary">Filtrar</button>
</form></section>
<div class="stats-banner" style="margin:1rem 0;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))"><div class="stat-box"><span class="stat-number"><?=e((string)count($rows))?></span><span class="stat-title">Registros</span></div><div class="stat-box"><span class="stat-number"><?=e((string)$total)?></span><span class="stat-title">Quantidade total</span></div></div>
<section class="box"><div style="overflow-x:auto"><table class="specs-table"><thead><tr><th>Data</th><th>Usuário</th><th>Serviço</th><th>Quantidade</th><th>Referência</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=e($r['criado_em'])?></td><td><?=e($r['usuario'])?></td><td><?=e($r['servico'])?></td><td><?=e((string)$r['quantidade']).' '.e($r['unidade_consumo'])?></td><td><?=e($r['referencia'])?></td></tr><?php endforeach; ?></tbody></table></div></section>
</div></main></body></html>
