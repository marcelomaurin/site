<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/auth.php';
$admin = exigirAdmin();

$msg = '';
$erro = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'salvar_servico') {
            $id = (int)($_POST['id'] ?? 0);
            $codigo = strtoupper(trim((string)($_POST['codigo'] ?? '')));
            $nome = trim((string)($_POST['nome'] ?? ''));
            $descricao = trim((string)($_POST['descricao'] ?? ''));
            $unidade = trim((string)($_POST['unidade_consumo'] ?? 'uso'));
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            if ($codigo === '' || $nome === '') throw new RuntimeException('Código e nome são obrigatórios.');

            if ($id > 0) {
                $st = db()->prepare('UPDATE servicos SET codigo=?,nome=?,descricao=?,unidade_consumo=?,ativo=? WHERE id=?');
                $st->execute([$codigo,$nome,$descricao,$unidade,$ativo,$id]);
            } else {
                $st = db()->prepare('INSERT INTO servicos(codigo,nome,descricao,unidade_consumo,ativo) VALUES(?,?,?,?,?)');
                $st->execute([$codigo,$nome,$descricao,$unidade,$ativo]);
            }
            registrarAuditoria((int)$admin['id'], 'SALVAR_SERVICO', 'servicos', ['codigo'=>$codigo]);
            $msg = 'Serviço salvo.';
        }

        if ($acao === 'salvar_plano_servico') {
            $planoId = (int)$_POST['plano_id'];
            $servicoId = (int)$_POST['servico_id'];
            $permitido = isset($_POST['permitido']) ? 1 : 0;
            $limite = trim((string)($_POST['limite_quantidade'] ?? ''));
            $periodo = (string)($_POST['periodo'] ?? 'mensal');
            $limiteDb = $limite === '' ? null : (float)$limite;

            $sql = "INSERT INTO plano_servicos(plano_id,servico_id,permitido,limite_quantidade,periodo)
                    VALUES(?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE permitido=VALUES(permitido),limite_quantidade=VALUES(limite_quantidade),periodo=VALUES(periodo)";
            db()->prepare($sql)->execute([$planoId,$servicoId,$permitido,$limiteDb,$periodo]);
            registrarAuditoria((int)$admin['id'], 'ALTERAR_LIMITE_PLANO', 'plano_servicos', compact('planoId','servicoId','permitido','limiteDb','periodo'));
            $msg = 'Regra do plano atualizada.';
        }

        if ($acao === 'salvar_usuario_servico') {
            $usuarioId = (int)$_POST['usuario_id'];
            $servicoId = (int)$_POST['servico_id'];
            $permitido = isset($_POST['permitido']) ? 1 : 0;
            $limite = trim((string)($_POST['limite_quantidade'] ?? ''));
            $periodo = (string)($_POST['periodo'] ?? 'mensal');
            $limiteDb = $limite === '' ? null : (float)$limite;

            $sql = "INSERT INTO usuario_servicos(usuario_id,servico_id,permitido,limite_quantidade,periodo)
                    VALUES(?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE permitido=VALUES(permitido),limite_quantidade=VALUES(limite_quantidade),periodo=VALUES(periodo)";
            db()->prepare($sql)->execute([$usuarioId,$servicoId,$permitido,$limiteDb,$periodo]);
            registrarAuditoria((int)$admin['id'], 'ALTERAR_CONCESSAO_USUARIO', 'usuario_servicos', compact('usuarioId','servicoId','permitido','limiteDb','periodo'));
            $msg = 'Concessão individual atualizada.';
        }

        if ($acao === 'alterar_plano_usuario') {
            $usuarioId = (int)$_POST['usuario_id'];
            $planoId = (int)$_POST['plano_id'];
            db()->prepare('UPDATE usuarios SET plano_id=? WHERE id=? AND papel="usuario"')->execute([$planoId ?: null,$usuarioId]);
            registrarAuditoria((int)$admin['id'], 'ALTERAR_PLANO_USUARIO', 'usuarios', compact('usuarioId','planoId'));
            $msg = 'Plano do usuário atualizado.';
        }
    }
} catch (Throwable $e) {
    $erro = $e->getMessage();
}

$planos = db()->query('SELECT * FROM planos WHERE ativo=1 ORDER BY tipo,nome')->fetchAll();
$servicos = db()->query('SELECT * FROM servicos ORDER BY nome')->fetchAll();
$usuarios = db()->query("SELECT u.id,u.nome,u.email,u.plano_id,p.nome plano_nome FROM usuarios u LEFT JOIN planos p ON p.id=u.plano_id WHERE u.ativo=1 AND u.papel='usuario' ORDER BY u.nome")->fetchAll();

$regrasPlano = db()->query("SELECT ps.*,p.nome plano_nome,s.nome servico_nome FROM plano_servicos ps JOIN planos p ON p.id=ps.plano_id JOIN servicos s ON s.id=ps.servico_id ORDER BY p.nome,s.nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Serviços e Limites | Maurinsoft</title>
<link rel="stylesheet" href="../../assets/css/main.css">
<style>.admin-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(330px,1fr));gap:1.2rem}.box{padding:1.4rem;border:1px solid var(--border-glass);border-radius:var(--radius-md);background:rgba(255,255,255,.03)}</style>
</head>
<body>
<header class="site-header"><div class="container nav-container">
<a href="../../index.html" class="brand-logo"><img src="../../assets/img/logo-maurinsoft.svg" class="site-logo-img" alt="Maurinsoft"></a>
<nav class="nav-menu"><a class="nav-link" href="index.php">Visão Geral</a><a class="nav-link active" href="servicos.php">Serviços e Limites</a></nav>
<div class="nav-cta"><a class="btn btn-secondary btn-sm" href="../logout.php">Sair</a></div>
</div></header>
<main class="section"><div class="container">
<div class="section-header" style="text-align:left"><span class="section-tag">Administração</span><h1 class="section-title">Serviços, planos e concessões</h1><p class="section-desc">O plano define a regra padrão. Uma concessão individual sobrescreve a regra do plano para aquele usuário.</p></div>
<?php if($msg): ?><div class="box" style="margin-bottom:1rem;color:var(--accent-green)"><?=e($msg)?></div><?php endif; ?>
<?php if($erro): ?><div class="box" style="margin-bottom:1rem;color:#fca5a5"><?=e($erro)?></div><?php endif; ?>

<div class="admin-grid">
<section class="box">
<h2 style="color:#fff">Cadastrar serviço</h2>
<form method="post">
<input type="hidden" name="acao" value="salvar_servico">
<div class="form-group"><label class="form-label">Código</label><input class="form-control" name="codigo" placeholder="EX: NOVO_SERVICO" required></div>
<div class="form-group"><label class="form-label">Nome</label><input class="form-control" name="nome" required></div>
<div class="form-group"><label class="form-label">Descrição</label><textarea class="form-control" name="descricao"></textarea></div>
<div class="form-group"><label class="form-label">Unidade de consumo</label><input class="form-control" name="unidade_consumo" value="uso"></div>
<label><input type="checkbox" name="ativo" checked> Ativo</label>
<div style="margin-top:1rem"><button class="btn btn-primary">Salvar serviço</button></div>
</form>
</section>

<section class="box">
<h2 style="color:#fff">Regra por plano</h2>
<form method="post">
<input type="hidden" name="acao" value="salvar_plano_servico">
<div class="form-group"><label class="form-label">Plano</label><select class="form-control" name="plano_id" required><?php foreach($planos as $p): ?><option value="<?=$p['id']?>"><?=e($p['nome'])?></option><?php endforeach; ?></select></div>
<div class="form-group"><label class="form-label">Serviço</label><select class="form-control" name="servico_id" required><?php foreach($servicos as $s): ?><option value="<?=$s['id']?>"><?=e($s['nome'])?></option><?php endforeach; ?></select></div>
<div class="form-group"><label class="form-label">Limite (vazio = ilimitado)</label><input class="form-control" type="number" step="0.01" min="0" name="limite_quantidade"></div>
<div class="form-group"><label class="form-label">Período</label><select class="form-control" name="periodo"><option>diario</option><option>semanal</option><option selected>mensal</option><option>anual</option><option>vitalicio</option></select></div>
<label><input type="checkbox" name="permitido" checked> Permitido</label>
<div style="margin-top:1rem"><button class="btn btn-primary">Aplicar regra</button></div>
</form>
</section>

<section class="box">
<h2 style="color:#fff">Plano do usuário</h2>
<form method="post">
<input type="hidden" name="acao" value="alterar_plano_usuario">
<div class="form-group"><label class="form-label">Usuário</label><select class="form-control" name="usuario_id" required><?php foreach($usuarios as $x): ?><option value="<?=$x['id']?>"><?=e($x['nome'].' — '.$x['email'])?></option><?php endforeach; ?></select></div>
<div class="form-group"><label class="form-label">Plano</label><select class="form-control" name="plano_id"><?php foreach($planos as $p): ?><option value="<?=$p['id']?>"><?=e($p['nome'])?></option><?php endforeach; ?></select></div>
<button class="btn btn-primary">Alterar plano</button>
</form>
</section>

<section class="box">
<h2 style="color:#fff">Concessão individual</h2>
<form method="post">
<input type="hidden" name="acao" value="salvar_usuario_servico">
<div class="form-group"><label class="form-label">Usuário</label><select class="form-control" name="usuario_id" required><?php foreach($usuarios as $x): ?><option value="<?=$x['id']?>"><?=e($x['nome'].' — '.$x['email'])?></option><?php endforeach; ?></select></div>
<div class="form-group"><label class="form-label">Serviço</label><select class="form-control" name="servico_id" required><?php foreach($servicos as $s): ?><option value="<?=$s['id']?>"><?=e($s['nome'])?></option><?php endforeach; ?></select></div>
<div class="form-group"><label class="form-label">Limite (vazio = ilimitado)</label><input class="form-control" type="number" step="0.01" min="0" name="limite_quantidade"></div>
<div class="form-group"><label class="form-label">Período</label><select class="form-control" name="periodo"><option>diario</option><option>semanal</option><option selected>mensal</option><option>anual</option><option>vitalicio</option></select></div>
<label><input type="checkbox" name="permitido" checked> Permitido</label>
<div style="margin-top:1rem"><button class="btn btn-primary">Salvar concessão</button></div>
</form>
</section>
</div>

<section class="box" style="margin-top:1.2rem">
<h2 style="color:#fff">Regras atualmente configuradas por plano</h2>
<div style="overflow-x:auto"><table class="specs-table"><thead><tr><th>Plano</th><th>Serviço</th><th>Permitido</th><th>Limite</th><th>Período</th></tr></thead><tbody>
<?php foreach($regrasPlano as $r): ?><tr><td><?=e($r['plano_nome'])?></td><td><?=e($r['servico_nome'])?></td><td><?=$r['permitido']?'Sim':'Não'?></td><td><?=e($r['limite_quantidade']===null?'Ilimitado':(string)$r['limite_quantidade'])?></td><td><?=e($r['periodo'])?></td></tr><?php endforeach; ?>
</tbody></table></div>
</section>
</div></main>
</body></html>
