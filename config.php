<?php
declare(strict_types=1);

session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'share_resources';
const DB_USER = 'root';
const DB_PASS = '';

const UPLOAD_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$appRoot = realpath(__DIR__);
$basePath = '';
if ($documentRoot && $appRoot && str_starts_with($appRoot, $documentRoot)) {
    $basePath = str_replace('\\', '/', substr($appRoot, strlen($documentRoot)));
}
define('BASE_PATH', rtrim($basePath, '/'));

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $pdo;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function app_url(string $path = ''): string
{
    $normalized = ltrim($path, '/');
    if ($normalized === '') {
        return BASE_PATH !== '' ? BASE_PATH : '/';
    }

    if (BASE_PATH === '') {
        return '/' . $normalized;
    }

    return BASE_PATH . '/' . $normalized;
}

function app_absolute_url(string $path = ''): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . app_url($path);
}

function redirectTo(string $path): void
{
    header('Location: ' . app_url($path));
    exit;
}

function isAdmin(): bool
{
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        redirectTo('admin/login.php');
    }
}
