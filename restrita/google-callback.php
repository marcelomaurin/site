<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

function voltarLoginGoogle(string $mensagem): never
{
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_state_expira']);
    header('Location: login.php?erro=' . urlencode($mensagem));
    exit;
}

if (!googleOAuthConfigurado()) {
    voltarLoginGoogle('Login Google não está configurado no servidor.');
}

if (!empty($_GET['error'])) {
    voltarLoginGoogle('Autenticação Google cancelada ou não autorizada.');
}

$stateRecebido = (string)($_GET['state'] ?? '');
$stateSessao = (string)($_SESSION['google_oauth_state'] ?? '');
$expira = (int)($_SESSION['google_oauth_state_expira'] ?? 0);

if (
    $stateRecebido === '' ||
    $stateSessao === '' ||
    !hash_equals($stateSessao, $stateRecebido) ||
    $expira < time()
) {
    voltarLoginGoogle('A sessão de autenticação Google expirou. Tente novamente.');
}

$code = trim((string)($_GET['code'] ?? ''));
if ($code === '') {
    voltarLoginGoogle('O Google não retornou o código de autorização.');
}

unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_state_expira']);

try {
    $token = httpPostForm('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => googleClientId(),
        'client_secret' => googleClientSecret(),
        'redirect_uri' => googleRedirectUri(),
        'grant_type' => 'authorization_code',
    ]);

    $accessToken = trim((string)($token['access_token'] ?? ''));
    if ($accessToken === '') {
        throw new RuntimeException('O Google não retornou um token de acesso.');
    }

    $perfil = httpGetBearer('https://openidconnect.googleapis.com/v1/userinfo', $accessToken);
    $u = loginGoogle($perfil);

    header('Location: ' . ($u['papel'] === 'admin' ? 'admin/index.php' : 'index.php'));
    exit;
} catch (Throwable $e) {
    registrarAuditoria(null, 'ERRO_LOGIN_GOOGLE', 'autenticacao', ['erro'=>$e->getMessage()]);
    voltarLoginGoogle('Não foi possível entrar com Google. ' . $e->getMessage());
}
