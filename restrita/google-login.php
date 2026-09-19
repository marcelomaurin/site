<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

if (!googleOAuthConfigurado()) {
    header('Location: login.php?erro=' . urlencode('Login Google ainda não foi configurado no servidor.'));
    exit;
}

$state = bin2hex(random_bytes(24));
$_SESSION['google_oauth_state'] = $state;
$_SESSION['google_oauth_state_expira'] = time() + 600;

$params = [
    'client_id' => googleClientId(),
    'redirect_uri' => googleRedirectUri(),
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'include_granted_scopes' => 'true',
    'prompt' => 'select_account',
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;
