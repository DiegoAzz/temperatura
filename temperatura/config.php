<?php
declare(strict_types=1);

function env_required(string $name): string
{
    $value = getenv($name);

    if ($value === false || $value === '') {
        throw new RuntimeException("Falta la variable de entorno: {$name}");
    }

    return $value;
}

define('DB_HOST', env_required('DB_HOST'));
define('DB_NAME', env_required('DB_NAME'));
define('DB_USER', env_required('DB_USER'));
define('DB_PASS', env_required('DB_PASS'));
define('API_KEY', env_required('API_KEY'));

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST .
        ';dbname=' . DB_NAME .
        ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function json_response(int $status, array $data): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
