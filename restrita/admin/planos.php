<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/auth.php';
$admin = exigirAdmin();
$msg=''; $erro='';

try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        $acao=$_POST['acao']??'';
        if ($acao==='salvar') {
            $id=(int)($_POST['id']??0);
            $codigo=strtoupper(trim((string)($_POST['codigo']??'')));
            $nome=trim((string)($_POST['nome']??''));
            $tipo=($_POST['tipo']??'gratuito')==='pago'?'pago':'gratuito';
            $descricao=trim((string)($_POST['descricao']??''));
            $preco=trim((string)($_POST['preco']??'')); $precoDb=$preco===''?null:(float)$preco;
            $periodicidade=(string)($_POST['periodicidade']??'mensal');
            $diasTeste=max(0,(int)($_POST['dias_teste']??0));
            $ativo=isset($_POST['ativo'])?1:0;
            if($codigo===''||$nome==='') throw new RuntimeException('Código e nome são obrigatórios.');
            if($id>0){
                db()->prepare('UPDATE planos SET codigo=?,nome=?,tipo=?,descricao=?,preco=?,periodicidade=?,dias_teste=?,ativo=? WHERE id=?')
                  ->execute([$codigo,$nome,$tipo,$descricao,$precoDb,$periodicidade,$diasTeste,$ativo,$id]);
            } else {
                db()->prepare('INSERT INTO planos(codigo,nome,tipo,descricao,preco,periodicidade,dias_teste,ativo) VALUES(?,?,?,?,?,?,?,?)')
                  ->execute([$codigo,$nome,$tipo,$descricao,$precoDb,$periodicidade,$diasTeste,$ativo]);
            }
            registrarAuditoria((int)$admin['id'],'SALVAR_PLANO','planos',['codigo'=>$codigo]);
            $msg='Plano salvo.';
        }
    }
} catch(Throwable $e){$erro=$e->getMessage();}

$planos=db()->query("SELECT p.*,
 (SELECT COUNT(*) FROM usuarios u WHERE u.plano_id=p.id) usuarios,
 (SELECT COUNT(*) FROM plano_servicos ps WHERE ps.plano_id=p.id AND ps.permitido=1) servicos
 FROM planos p ORDER BY p.tipo,p.nome")->fetchAll();
?>
<!DOCTYPE html><html lang="pt-BR"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Planos | Maurinsoft</title><link rel="stylesheet" href="../../assets/css/main.css">
<style>.box{padding:1.4rem;border:1px solid var(--border-glass);border-radius:var(--radius-md);background:rgba(255,255,255,.03)}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem}</style>
</head><body>
<header class="site-header"><div class="container nav-container">
<a href="../../index.html" class="brand-logo"><img src="../../assets/img/logo-maurinsoft.svg" class="site-logo-img" alt="Maurinsoft"></a>
<nav class="nav-menu"><a class="nav-link" href="index.php">Visão Geral</a><a class="nav-link" href="usuarios.php">Usuários</a><a class="nav-link active" href="planos.php">Planos</a><a class="nav-link" href="assinaturas.php">Assinaturas</a><a class="nav-link" href="servicos.php">Serviços</a><a class="nav-link" href="consumo.php">Consumo</a><a class="nav-link" href="auditoria.php">Auditoria</a></nav>
<div class="nav-cta"><a class="btn btn-secondary btn-sm" href="../logout.php">Sair</a></div></div></header>
<main class="section"><div class="container">
<div class="section-header" style="text-align:left"><span class="section-tag">Administração</span><h1 class="section-title">Planos</h1><p class="section-desc">Defina planos gratuitos e pagos, valores, periodicidade e período de teste.</p></div>
<?php if($msg): ?><div class="box" style="margin-bottom:1rem;color:var(--accent-green)"><?=e($msg)?></div><?php endif; ?><?php if($erro): ?><div class="box" style="margin-bottom:1rem;color:#fca5a5"><?=e($erro)?></div><?php endif; ?>
<div class="grid">
<section class="box"><h2 style="color:#fff">Novo plano</h2><form method="post"><input type="hidden" name="acao" value="salvar">
<div class="form-group"><label class="form-label">Código</label><input class="form-control" name="codigo" required></div>
<div class="form-group"><label class="form-label">Nome</label><input class="form-control" name="nome" required></div>
<div class="form-group"><label class="form-label">Tipo</label><select class="form-control" name="tipo"><option value="gratuito">Gratuito</option><option value="pago">Pago</option></select></div>
<div class="form-group"><label class="form-label">Descrição</label><textarea class="form-control" name="descricao"></textarea></div>
<div class="form-group"><label class="form-label">Preço</label><input class="form-control" type="number" step="0.01" min="0" name="preco"></div>
<div class="form-group"><label class="form-label">Periodicidade</label><select class="form-control" name="periodicidade"><option>mensal</option><option>trimestral</option><option>semestral</option><option>anual</option><option>vitalicio</option></select></div>
<div class="form-group"><label class="form-label">Dias de teste</label><input class="form-control" type="number" min="0" name="dias_teste" value="0"></div>
<label><input type="checkbox" name="ativo" checked> Ativo</label><div style="margin-top:1rem"><button class="btn btn-primary">Salvar plano</button></div>
</form></section>

<section class="box"><h2 style="color:#fff">Planos cadastrados</h2><div style="overflow-x:auto"><table class="specs-table"><thead><tr><th>Plano</th><th>Tipo</th><th>Preço</th><th>Usuários</th><th>Serviços</th><th>Status</th></tr></thead><tbody>
<?php foreach($planos as $p): ?><tr><td><strong><?=e($p['nome'])?></strong><div style="font-size:.8rem;color:var(--text-dim)"><?=e($p['codigo'])?></div></td><td><?=e($p['tipo'])?></td><td><?=$p['preco']===null?'—':'R$ '.number_format((float)$p['preco'],2,',','.')?></td><td><?=e((string)$p['usuarios'])?></td><td><?=e((string)$p['servicos'])?></td><td><?=$p['ativo']?'Ativo':'Inativo'?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
</div>
</div></main></body></html>
