<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/auth.php';
$admin=exigirAdmin(); $msg=''; $erro='';

try{
if($_SERVER['REQUEST_METHOD']==='POST'){
  $acao=$_POST['acao']??'';
  if($acao==='criar'){
    $usuarioId=(int)$_POST['usuario_id']; $planoId=(int)$_POST['plano_id'];
    $status=(string)($_POST['status']??'ativa');
    $inicio=(string)($_POST['inicio']??''); $fim=trim((string)($_POST['fim']??''));
    $renovacao=isset($_POST['renovacao_automatica'])?1:0;
    $obs=trim((string)($_POST['observacao']??''));
    if(!$usuarioId||!$planoId) throw new RuntimeException('Usuário e plano são obrigatórios.');
    db()->prepare('INSERT INTO assinaturas(usuario_id,plano_id,status,inicio,fim,renovacao_automatica,observacao) VALUES(?,?,?,?,?,?,?)')
      ->execute([$usuarioId,$planoId,$status,$inicio?:date('Y-m-d H:i:s'),$fim?:null,$renovacao,$obs]);
    db()->prepare('UPDATE usuarios SET plano_id=? WHERE id=?')->execute([$planoId,$usuarioId]);
    registrarAuditoria((int)$admin['id'],'CRIAR_ASSINATURA','assinaturas',['usuario_id'=>$usuarioId,'plano_id'=>$planoId,'status'=>$status]);
    $msg='Assinatura criada.';
  }
  if($acao==='status'){
    $id=(int)$_POST['id']; $status=(string)$_POST['status'];
    db()->prepare('UPDATE assinaturas SET status=? WHERE id=?')->execute([$status,$id]);
    registrarAuditoria((int)$admin['id'],'ALTERAR_STATUS_ASSINATURA','assinaturas',['assinatura_id'=>$id,'status'=>$status]);
    $msg='Status da assinatura alterado.';
  }
}}
catch(Throwable $e){$erro=$e->getMessage();}

$usuarios=db()->query("SELECT id,nome,email FROM usuarios WHERE ativo=1 AND papel='usuario' ORDER BY nome")->fetchAll();
$planos=db()->query("SELECT id,nome FROM planos WHERE ativo=1 ORDER BY nome")->fetchAll();
$assinaturas=db()->query("SELECT a.*,u.nome usuario,u.email,p.nome plano,p.preco FROM assinaturas a JOIN usuarios u ON u.id=a.usuario_id JOIN planos p ON p.id=a.plano_id ORDER BY a.id DESC")->fetchAll();
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Assinaturas | Maurinsoft</title><link rel="stylesheet" href="../../assets/css/main.css">
<style>.box{padding:1.4rem;border:1px solid var(--border-glass);border-radius:var(--radius-md);background:rgba(255,255,255,.03)}.grid{display:grid;grid-template-columns:minmax(320px,420px) 1fr;gap:1rem}@media(max-width:900px){.grid{grid-template-columns:1fr}}</style></head><body>
<header class="site-header"><div class="container nav-container"><a href="../../index.html" class="brand-logo"><img src="../../assets/img/logo-maurinsoft.svg" class="site-logo-img" alt="Maurinsoft"></a><nav class="nav-menu"><a class="nav-link" href="index.php">Visão Geral</a><a class="nav-link" href="usuarios.php">Usuários</a><a class="nav-link" href="planos.php">Planos</a><a class="nav-link active" href="assinaturas.php">Assinaturas</a><a class="nav-link" href="servicos.php">Serviços</a><a class="nav-link" href="consumo.php">Consumo</a><a class="nav-link" href="auditoria.php">Auditoria</a></nav><div class="nav-cta"><a class="btn btn-secondary btn-sm" href="../logout.php">Sair</a></div></div></header>
<main class="section"><div class="container"><div class="section-header" style="text-align:left"><span class="section-tag">Administração</span><h1 class="section-title">Assinaturas</h1><p class="section-desc">Controle vigência, situação e renovação dos planos contratados.</p></div>
<?php if($msg): ?><div class="box" style="margin-bottom:1rem;color:var(--accent-green)"><?=e($msg)?></div><?php endif; ?><?php if($erro): ?><div class="box" style="margin-bottom:1rem;color:#fca5a5"><?=e($erro)?></div><?php endif; ?>
<div class="grid"><section class="box"><h2 style="color:#fff">Nova assinatura</h2><form method="post"><input type="hidden" name="acao" value="criar">
<div class="form-group"><label class="form-label">Usuário</label><select class="form-control" name="usuario_id" required><?php foreach($usuarios as $u): ?><option value="<?=$u['id']?>"><?=e($u['nome'].' — '.$u['email'])?></option><?php endforeach; ?></select></div>
<div class="form-group"><label class="form-label">Plano</label><select class="form-control" name="plano_id" required><?php foreach($planos as $p): ?><option value="<?=$p['id']?>"><?=e($p['nome'])?></option><?php endforeach; ?></select></div>
<div class="form-group"><label class="form-label">Status</label><select class="form-control" name="status"><option>teste</option><option selected>ativa</option><option>suspensa</option><option>cancelada</option><option>expirada</option></select></div>
<div class="form-group"><label class="form-label">Início</label><input class="form-control" type="datetime-local" name="inicio"></div>
<div class="form-group"><label class="form-label">Fim</label><input class="form-control" type="datetime-local" name="fim"></div>
<label><input type="checkbox" name="renovacao_automatica"> Renovação automática</label>
<div class="form-group" style="margin-top:1rem"><label class="form-label">Observação</label><input class="form-control" name="observacao"></div>
<button class="btn btn-primary">Criar assinatura</button></form></section>

<section class="box"><h2 style="color:#fff">Histórico</h2><div style="overflow-x:auto"><table class="specs-table"><thead><tr><th>Usuário</th><th>Plano</th><th>Vigência</th><th>Status</th><th>Ação</th></tr></thead><tbody>
<?php foreach($assinaturas as $a): ?><tr><td><?=e($a['usuario'])?><div style="font-size:.8rem;color:var(--text-dim)"><?=e($a['email'])?></div></td><td><?=e($a['plano'])?></td><td><?=e($a['inicio'])?><br><?=e($a['fim']??'Sem término')?></td><td><?=e($a['status'])?></td><td><form method="post" style="display:flex;gap:.4rem"><input type="hidden" name="acao" value="status"><input type="hidden" name="id" value="<?=$a['id']?>"><select class="form-control" name="status"><option <?=$a['status']==='teste'?'selected':''?>>teste</option><option <?=$a['status']==='ativa'?'selected':''?>>ativa</option><option <?=$a['status']==='suspensa'?'selected':''?>>suspensa</option><option <?=$a['status']==='cancelada'?'selected':''?>>cancelada</option><option <?=$a['status']==='expirada'?'selected':''?>>expirada</option></select><button class="btn btn-secondary btn-sm">Salvar</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></section></div></div></main></body></html>
