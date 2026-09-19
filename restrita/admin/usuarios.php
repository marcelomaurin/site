<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/auth.php';
$admin = exigirAdmin();

$msg = '';
$erro = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'criar') {
            $nome = trim((string)($_POST['nome'] ?? ''));
            $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
            $senha = (string)($_POST['senha'] ?? '');
            $papel = ($_POST['papel'] ?? 'usuario') === 'admin' ? 'admin' : 'usuario';
            $planoId = (int)($_POST['plano_id'] ?? 0);

            if ($nome === '' || $email === '' || $senha === '') {
                throw new RuntimeException('Nome, e-mail e senha são obrigatórios.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('E-mail inválido.');
            }

            $st = db()->prepare('INSERT INTO usuarios(nome,email,senha_hash,papel,plano_id,ativo) VALUES(?,?,?,?,?,1)');
            $st->execute([$nome,$email,password_hash($senha,PASSWORD_DEFAULT),$papel,$planoId ?: null]);
            $id = (int)db()->lastInsertId();

            registrarAuditoria((int)$admin['id'],'CRIAR_USUARIO','usuarios',['usuario_id'=>$id,'email'=>$email,'papel'=>$papel]);
            $msg = 'Usuário criado com sucesso.';
        }

        if ($acao === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)$admin['id']) throw new RuntimeException('Você não pode bloquear sua própria conta.');
            $st = db()->prepare('UPDATE usuarios SET ativo=IF(ativo=1,0,1) WHERE id=?');
            $st->execute([$id]);
            registrarAuditoria((int)$admin['id'],'ALTERAR_STATUS_USUARIO','usuarios',['usuario_id'=>$id]);
            $msg = 'Status do usuário alterado.';
        }

        if ($acao === 'senha') {
            $id = (int)($_POST['id'] ?? 0);
            $senha = (string)($_POST['nova_senha'] ?? '');
            if (strlen($senha) < 6) throw new RuntimeException('A nova senha deve ter pelo menos 6 caracteres.');
            db()->prepare('UPDATE usuarios SET senha_hash=? WHERE id=?')->execute([password_hash($senha,PASSWORD_DEFAULT),$id]);
            registrarAuditoria((int)$admin['id'],'REDEFINIR_SENHA','usuarios',['usuario_id'=>$id]);
            $msg = 'Senha redefinida.';
        }

        if ($acao === 'editar') {
            $id = (int)($_POST['id'] ?? 0);
            $nome = trim((string)($_POST['nome'] ?? ''));
            $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
            $papel = ($_POST['papel'] ?? 'usuario') === 'admin' ? 'admin' : 'usuario';
            $planoId = (int)($_POST['plano_id'] ?? 0);

            if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Nome ou e-mail inválido.');
            }

            db()->prepare('UPDATE usuarios SET nome=?,email=?,papel=?,plano_id=? WHERE id=?')
                ->execute([$nome,$email,$papel,$planoId ?: null,$id]);
            registrarAuditoria((int)$admin['id'],'EDITAR_USUARIO','usuarios',['usuario_id'=>$id]);
            $msg = 'Usuário atualizado.';
        }
    }
} catch (Throwable $e) {
    $erro = $e->getMessage();
}

$planos = db()->query("SELECT id,nome FROM planos WHERE ativo=1 ORDER BY nome")->fetchAll();
$usuarios = db()->query("SELECT u.*,p.nome plano_nome,
    (SELECT COUNT(*) FROM assinaturas a WHERE a.usuario_id=u.id AND a.status IN ('ativa','teste')) assinaturas_ativas
    FROM usuarios u
    LEFT JOIN planos p ON p.id=u.plano_id
    ORDER BY u.nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Usuários | Maurinsoft</title>
<link rel="stylesheet" href="../../assets/css/main.css">
<style>
.admin-grid{display:grid;grid-template-columns:minmax(320px,420px) 1fr;gap:1.2rem}
.box{padding:1.4rem;border:1px solid var(--border-glass);border-radius:var(--radius-md);background:rgba(255,255,255,.03)}
.inline-actions{display:flex;gap:.5rem;flex-wrap:wrap}
@media(max-width:900px){.admin-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<header class="site-header"><div class="container nav-container">
<a href="../../index.html" class="brand-logo"><img src="../../assets/img/logo-maurinsoft.svg" class="site-logo-img" alt="Maurinsoft"></a>
<nav class="nav-menu">
<a class="nav-link" href="index.php">Visão Geral</a>
<a class="nav-link active" href="usuarios.php">Usuários</a>
<a class="nav-link" href="planos.php">Planos</a>
<a class="nav-link" href="assinaturas.php">Assinaturas</a>
<a class="nav-link" href="servicos.php">Serviços</a>
<a class="nav-link" href="consumo.php">Consumo</a>
<a class="nav-link" href="auditoria.php">Auditoria</a>
</nav>
<div class="nav-cta"><a class="btn btn-secondary btn-sm" href="../logout.php">Sair</a></div>
</div></header>
<main class="section"><div class="container">
<div class="section-header" style="text-align:left"><span class="section-tag">Administração</span><h1 class="section-title">Usuários</h1><p class="section-desc">Cadastre, edite, bloqueie e associe usuários aos planos disponíveis.</p></div>
<?php if($msg): ?><div class="box" style="margin-bottom:1rem;color:var(--accent-green)"><?=e($msg)?></div><?php endif; ?>
<?php if($erro): ?><div class="box" style="margin-bottom:1rem;color:#fca5a5"><?=e($erro)?></div><?php endif; ?>

<div class="admin-grid">
<section class="box">
<h2 style="color:#fff">Novo usuário</h2>
<form method="post">
<input type="hidden" name="acao" value="criar">
<div class="form-group"><label class="form-label">Nome</label><input class="form-control" name="nome" required></div>
<div class="form-group"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" required></div>
<div class="form-group"><label class="form-label">Senha inicial</label><input class="form-control" type="password" name="senha" required></div>
<div class="form-group"><label class="form-label">Papel</label><select class="form-control" name="papel"><option value="usuario">Usuário</option><option value="admin">Administrador</option></select></div>
<div class="form-group"><label class="form-label">Plano</label><select class="form-control" name="plano_id"><option value="">Sem plano</option><?php foreach($planos as $p): ?><option value="<?=$p['id']?>"><?=e($p['nome'])?></option><?php endforeach; ?></select></div>
<button class="btn btn-primary">Criar usuário</button>
</form>
</section>

<section class="box">
<h2 style="color:#fff">Usuários cadastrados</h2>
<div style="overflow-x:auto">
<table class="specs-table">
<thead><tr><th>Usuário</th><th>Papel</th><th>Plano</th><th>Status</th><th>Assinaturas</th><th>Ações</th></tr></thead>
<tbody>
<?php foreach($usuarios as $u): ?>
<tr>
<td><strong><?=e($u['nome'])?></strong><div style="font-size:.8rem;color:var(--text-dim)"><?=e($u['email'])?></div></td>
<td><?=e($u['papel'])?></td>
<td><?=e($u['plano_nome'] ?? '—')?></td>
<td><?=$u['ativo']?'Ativo':'Bloqueado'?></td>
<td><?=e((string)$u['assinaturas_ativas'])?></td>
<td>
<div class="inline-actions">
<form method="post"><input type="hidden" name="acao" value="toggle"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn btn-secondary btn-sm"><?=$u['ativo']?'Bloquear':'Ativar'?></button></form>
<details>
<summary class="btn btn-secondary btn-sm" style="cursor:pointer">Editar</summary>
<form method="post" style="min-width:280px;padding:1rem">
<input type="hidden" name="acao" value="editar"><input type="hidden" name="id" value="<?=$u['id']?>">
<input class="form-control" name="nome" value="<?=e($u['nome'])?>" style="margin-bottom:.5rem">
<input class="form-control" name="email" value="<?=e($u['email'])?>" style="margin-bottom:.5rem">
<select class="form-control" name="papel" style="margin-bottom:.5rem"><option value="usuario" <?=$u['papel']==='usuario'?'selected':''?>>Usuário</option><option value="admin" <?=$u['papel']==='admin'?'selected':''?>>Administrador</option></select>
<select class="form-control" name="plano_id" style="margin-bottom:.5rem"><option value="">Sem plano</option><?php foreach($planos as $p): ?><option value="<?=$p['id']?>" <?=$u['plano_id']==$p['id']?'selected':''?>><?=e($p['nome'])?></option><?php endforeach; ?></select>
<button class="btn btn-primary btn-sm">Salvar</button>
</form>
</details>
<details>
<summary class="btn btn-secondary btn-sm" style="cursor:pointer">Senha</summary>
<form method="post" style="min-width:240px;padding:1rem">
<input type="hidden" name="acao" value="senha"><input type="hidden" name="id" value="<?=$u['id']?>">
<input class="form-control" type="password" name="nova_senha" placeholder="Nova senha" required style="margin-bottom:.5rem">
<button class="btn btn-primary btn-sm">Redefinir</button>
</form>
</details>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>
</div>
</div></main>
</body></html>
