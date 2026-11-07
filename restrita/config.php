<?php
declare(strict_types=1);

/*
 * Configuração de Banco de Dados e Ambiente - Espaço Restrito Maurinsoft
 * 
 * Ordem de prioridade:
 * 1. Arquivo local protegido: restrita/config.local.php (não versionado no Git)
 * 2. Variáveis de ambiente: DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT
 * 3. Valores padrão de fallback
 */

$localConfigFile = __DIR__ . '/config.local.php';
if (is_file($localConfigFile)) {
    require_once $localConfigFile;
}

function envValue(string $name, string $default = ''): string
{
    $v = getenv($name);
    return $v === false ? $default : trim((string)$v);
}

if (!defined('DB_HOST')) {
    define('DB_HOST', envValue('DB_HOST', 'localhost'));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', envValue('DB_PORT', '3306'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', envValue('DB_NAME', 'u820932905_maurinsoftdb'));
}
if (!defined('DB_USER')) {
    define('DB_USER', envValue('DB_USER', 'u820932905_mmm'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', envValue('DB_PASS', 'Marcella@12345'));
}

function dbConfigurado(): bool
{
    return defined('DB_PASS') && DB_PASS !== '' && DB_PASS !== 'ALTERE_AQUI';
}

/*
 * OAuth Google
 * Configure no servidor ou em config.local.php:
 *   GOOGLE_CLIENT_ID
 *   GOOGLE_CLIENT_SECRET
 *   GOOGLE_REDIRECT_URI
 */
function googleClientId(): string
{
    return envValue('GOOGLE_CLIENT_ID');
}

function googleClientSecret(): string
{
    return envValue('GOOGLE_CLIENT_SECRET');
}

function googleRedirectUri(): string
{
    return envValue('GOOGLE_REDIRECT_URI', 'https://maurinsoft.com.br/restrita/google-callback.php');
}

function googleOAuthConfigurado(): bool
{
    return googleClientId() !== '' && googleClientSecret() !== '' && googleRedirectUri() !== '';
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    session_name('MAURINSOFTSESSID');
    session_start([
        'cookie_path' => '/',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => $isHttps,
        'use_strict_mode' => false,
    ]);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!dbConfigurado()) {
        throw new PDOException('Credenciais do banco de dados não configuradas. Configure restrita/config.local.php no servidor.');
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
