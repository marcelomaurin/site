<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
$u = exigirLogin();

if ($u['papel'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

$servicos = db()->query("SELECT codigo,nome,descricao,unidade_consumo FROM servicos WHERE ativo=1 ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Meus Serviços | Maurinsoft</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
.service-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem}
.service-card{padding:1.4rem;border:1px solid var(--border-glass);border-radius:var(--radius-md);background:rgba(255,255,255,.03)}
.meter{height:8px;border-radius:999px;background:rgba(255,255,255,.08);overflow:hidden;margin-top:.65rem}.meter>span{display:block;height:100%;background:var(--primary)}
</style>
</head>
<body>
<header class="restricted-header"><div class="container restricted-topbar">
  <a href="../index.html" class="brand-logo"><img src="../assets/img/logo-maurinsoft.svg" class="site-logo-img" alt="Maurinsoft"></a>
  <div style="display:flex;gap:1rem;align-items:center"><span><?=e($u['nome'])?> • <?=e($u['plano_nome'] ?? 'Sem plano')?></span><a class="btn btn-secondary btn-sm" href="logout.php">Sair</a></div>
</div></header>
<main class="section"><div class="container">
  <div class="section-header" style="text-align:left">
    <span class="section-tag">Minha Conta</span>
    <h1 class="section-title">Serviços disponíveis</h1>
    <p class="section-desc">Seu acesso é calculado pelo plano contratado e por concessões específicas da sua conta. O consumo é medido automaticamente.</p>
  </div>
  <div class="service-grid">
  <?php foreach ($servicos as $s):
      $a = acessoServico((int)$u['id'], $s['codigo']);
      $ok = !empty($a['permitido']);
      $lim = $a['limite'] ?? null;
      $usado = (float)($a['usado'] ?? 0);
      $pct = ($lim && $lim > 0) ? min(100, ($usado/$lim)*100) : 0;
  ?>
    <article class="service-card">
      <div style="display:flex;justify-content:space-between;gap:1rem">
        <h3 style="color:#fff"><?=e($s['nome'])?></h3>
        <span class="tag-pill"><?=$ok ? 'Disponível' : e($a['motivo'] ?? 'Indisponível')?></span>
      </div>
      <p style="color:var(--text-muted)"><?=e($s['descricao'])?></p>
      <?php if ($ok || isset($a['usado'])): ?>
        <div style="font-size:.88rem;color:var(--text-dim)">
          Uso no período: <strong><?=e((string)$usado)?></strong>
          <?php if ($lim !== null): ?> de <strong><?=e((string)$lim)?></strong> <?=e($s['unidade_consumo'])?>(s)<?php else: ?> • sem limite quantitativo<?php endif; ?>
        </div>
        <?php if ($lim !== null): ?><div class="meter"><span style="width:<?=$pct?>%"></span></div><?php endif; ?>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
  </div>
</div></main>
</body></html>
