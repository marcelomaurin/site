<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

if ($u = usuarioAtual()) {
    header('Location: ' . ($u['papel'] === 'admin' ? 'admin/index.php' : 'index.php'));
    exit;
}

$erro = trim((string)($_GET['erro'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = (string)($_POST['senha'] ?? '');

    if (loginUsuario($email, $senha)) {
        $u = usuarioAtual();
        header('Location: ' . ($u && $u['papel'] === 'admin' ? 'admin/index.php' : 'index.php'));
        exit;
    }
    $erro = 'Usuário/e-mail ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Espaço Restrito | Maurinsoft</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    .login-wrapper{min-height:calc(100vh - 80px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}
    .login-card{width:100%;max-width:460px;background:rgba(13,20,36,.94);border:1px solid var(--border-accent);border-radius:var(--radius-lg);padding:3rem 2.5rem;box-shadow:var(--shadow-lg),0 0 50px rgba(0,210,255,.15)}
    .login-badge{display:inline-flex;gap:6px;padding:4px 12px;border-radius:999px;background:rgba(0,210,255,.1);border:1px solid rgba(0,210,255,.25);color:var(--primary);font-size:.78rem;font-weight:700;margin-bottom:1.5rem}
    .login-note{margin-top:1.5rem;color:var(--text-dim);font-size:.82rem;line-height:1.5}
    .separator{display:flex;align-items:center;gap:.8rem;margin:1.2rem 0;color:var(--text-dim);font-size:.82rem}
    .separator:before,.separator:after{content:"";height:1px;flex:1;background:var(--border-glass)}
    .google-btn{width:100%;display:flex;align-items:center;justify-content:center;gap:.7rem;background:#fff;color:#1f1f1f;border:1px solid #dadce0;border-radius:8px;padding:.8rem 1rem;font-weight:600;text-decoration:none}
    .google-btn:hover{background:#f8f9fa}
    .google-g{font-weight:800;font-size:1.1rem;color:#4285f4}
  </style>
</head>
<body>
<header class="site-header"><div class="container nav-container">
  <a href="../index.html" class="brand-logo"><img src="../assets/img/logo-maurinsoft.svg" alt="Maurinsoft" class="site-logo-img"></a>
  <a href="../index.html" class="btn btn-secondary btn-sm">&larr; Portal Público</a>
</div></header>
<main class="login-wrapper">
  <section class="login-card">
    <div class="login-badge">🔒 AMBIENTE SEGURO • PRESTAÇÃO DE SERVIÇOS</div>
    <h1 style="text-align:center;color:#fff;margin-bottom:.5rem">Espaço Restrito</h1>
    <p style="text-align:center;color:var(--text-muted);margin-bottom:2rem">Entre com sua conta. Seu perfil, plano, serviços e limites são identificados automaticamente.</p>

    <?php if ($erro): ?><div style="padding:10px;margin-bottom:1rem;border-radius:6px;background:rgba(239,68,68,.15);border:1px solid #ef4444;color:#fca5a5"><?=e($erro)?></div><?php endif; ?>

    <?php if (googleOAuthConfigurado()): ?>
      <a class="google-btn" href="google-login.php"><span class="google-g">G</span> Continuar com Google</a>
      <div class="separator">ou</div>
    <?php else: ?>
      <div style="padding:10px;margin-bottom:1rem;border-radius:6px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.35);color:#fbbf24;font-size:.82rem">
        Login Google disponível após configurar GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET no servidor.
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <div class="form-group">
        <label class="form-label" for="email">E-mail</label>
        <input class="form-control" id="email" name="email" type="email" required autocomplete="username">
      </div>
      <div class="form-group">
        <label class="form-label" for="senha">Senha</label>
        <input class="form-control" id="senha" name="senha" type="password" required autocomplete="current-password">
      </div>
      <button class="btn btn-primary btn-lg" style="width:100%" type="submit">Entrar no Espaço Restrito</button>
    </form>
    <div class="login-note">Contas Google novas entram como usuário gratuito. Privilégios administrativos nunca são concedidos automaticamente.</div>
  </section>
</main>
</body>
</html>
