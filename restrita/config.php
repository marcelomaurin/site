<?php
declare(strict_types=1);

/*
 * Configuração de Banco de Dados e Ambiente - Espaço Restrito Maurinsoft
 * 
 * Ordem de prioridade para configuração:
 * 1. Arquivo config.php da raiz do site (/public_html/config.php)
 * 2. Arquivo variaveis.php da raiz do site (/public_html/variaveis.php)
 * 3. Variáveis de ambiente: DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT
 * 4. Valores padrão de produção do servidor
 */

// 1. Carrega o config.php da raiz do site (public_html/config.php)
$rootConfigFile = dirname(__DIR__) . '/config.php';
if (is_file($rootConfigFile) && realpath($rootConfigFile) !== realpath(__FILE__)) {
    @include_once $rootConfigFile;
}

// 2. Carrega variaveis.php da raiz do site (se existir)
$rootVariaveisFile = dirname(__DIR__) . '/variaveis.php';
if (is_file($rootVariaveisFile)) {
    @include_once $rootVariaveisFile;
}

function envValue(string $name, string $default = ''): string
{
    $v = getenv($name);
    return $v === false ? $default : trim((string)$v);
}

// Mapeia configurações da raiz ($host, $dbname, $user, $password, etc.) ou variáveis de ambiente
if (!defined('DB_HOST')) {
    $val = $host ?? $db_host ?? envValue('DB_HOST', 'localhost');
    define('DB_HOST', (string)$val);
}
if (!defined('DB_PORT')) {
    $val = $port ?? $db_port ?? envValue('DB_PORT', '3306');
    define('DB_PORT', (string)$val);
}
if (!defined('DB_NAME')) {
    $val = $dbname ?? $db_name ?? envValue('DB_NAME', '');
    define('DB_NAME', (string)$val);
}
if (!defined('DB_USER')) {
    $val = $user ?? $db_user ?? envValue('DB_USER', '');
    define('DB_USER', (string)$val);
}
if (!defined('DB_PASS')) {
    $val = $password ?? $db_pass ?? $pass ?? envValue('DB_PASS', '');
    define('DB_PASS', (string)$val);
}

function dbConfigurado(): bool
{
    return defined('DB_PASS') && DB_PASS !== '' && defined('DB_NAME') && DB_NAME !== '' && defined('DB_USER') && DB_USER !== '';
}

/*
 * OAuth Google
 * Configure no config.php da raiz ou no ambiente do servidor:
 *   GOOGLE_CLIENT_ID
 *   GOOGLE_CLIENT_SECRET
 *   GOOGLE_REDIRECT_URI
 */
function googleClientId(): string
{
    global $google_client_id;
    return defined('GOOGLE_CLIENT_ID') ? (string)GOOGLE_CLIENT_ID : ($google_client_id ?? envValue('GOOGLE_CLIENT_ID'));
}

function googleClientSecret(): string
{
    global $google_client_secret;
    return defined('GOOGLE_CLIENT_SECRET') ? (string)GOOGLE_CLIENT_SECRET : ($google_client_secret ?? envValue('GOOGLE_CLIENT_SECRET'));
}

function googleRedirectUri(): string
{
    global $google_redirect_uri;
    return defined('GOOGLE_REDIRECT_URI') ? (string)GOOGLE_REDIRECT_URI : ($google_redirect_uri ?? envValue('GOOGLE_REDIRECT_URI', 'https://maurinsoft.com.br/restrita/google-callback.php'));
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

    // Se o config da raiz já criou uma instância PDO reutilizável
    global $pdoRoot, $conn;
    if (isset($pdoRoot) && $pdoRoot instanceof PDO) {
        $pdo = $pdoRoot;
        return $pdo;
    }
    if (isset($conn) && $conn instanceof PDO) {
        $pdo = $conn;
        return $pdo;
    }

    if (!dbConfigurado()) {
        throw new PDOException('Credenciais do banco de dados não configuradas. Verifique o arquivo config.php da raiz.');
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
