<?php
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'maurinsoft';
const DB_USER = 'maurinsoft';
const DB_PASS = 'ALTERE_AQUI';

/*
 * OAuth Google
 * Configure no servidor:
 *   GOOGLE_CLIENT_ID
 *   GOOGLE_CLIENT_SECRET
 *   GOOGLE_REDIRECT_URI
 */
function envValue(string $name, string $default = ''): string
{
    $v = getenv($name);
    return $v === false ? $default : trim((string)$v);
}

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
    session_name('MAURINSOFTSESSID');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'use_strict_mode' => true,
    ]);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
