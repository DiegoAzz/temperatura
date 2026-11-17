<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

date_default_timezone_set('America/Bogota');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    json_response(204, []);
}

$deviceId = trim((string) ($_GET['device_id'] ?? 'esp8266-sysnet-01'));

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = db()->prepare(
        'SELECT device_id, display_name, temp_min, temp_max, check_interval, telegram_enabled, updated_at
         FROM device_config
         WHERE device_id = :device_id
         LIMIT 1'
    );
    $stmt->execute([':device_id' => $deviceId]);
    $config = $stmt->fetch();

    if (!$config) {
        json_response(404, ['error' => 'Configuracion no encontrada']);
    }

    json_response(200, ['config' => $config]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['error' => 'Metodo no permitido']);
}

$receivedApiKey = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['api_key'] ?? '');
if ($receivedApiKey !== API_KEY) {
    json_response(401, ['error' => 'API key invalida']);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    json_response(400, ['error' => 'JSON invalido']);
}

$newDeviceId = trim((string) ($payload['device_id'] ?? $deviceId));
$displayName = trim((string) ($payload['display_name'] ?? 'Nevera SYSNET'));
$tempMin = isset($payload['temp_min']) ? (float) $payload['temp_min'] : 2.0;
$tempMax = isset($payload['temp_max']) ? (float) $payload['temp_max'] : 8.0;
$checkInterval = isset($payload['check_interval']) ? (int) $payload['check_interval'] : 30;
$telegramEnabled = !empty($payload['telegram_enabled']) ? 1 : 0;

if ($newDeviceId === '' || $displayName === '') {
    json_response(400, ['error' => 'Nombre de dispositivo requerido']);
}

if ($tempMin >= $tempMax) {
    json_response(400, ['error' => 'La temperatura minima debe ser menor que la maxima']);
}

$checkInterval = max(10, min($checkInterval, 3600));

$stmt = db()->prepare(
    'INSERT INTO device_config
        (device_id, display_name, temp_min, temp_max, check_interval, telegram_enabled, updated_at)
     VALUES
        (:device_id, :display_name, :temp_min, :temp_max, :check_interval, :telegram_enabled, :updated_at)
     ON DUPLICATE KEY UPDATE
        display_name = VALUES(display_name),
        temp_min = VALUES(temp_min),
        temp_max = VALUES(temp_max),
        check_interval = VALUES(check_interval),
        telegram_enabled = VALUES(telegram_enabled),
        updated_at = VALUES(updated_at)'
);

$updatedAt = date('Y-m-d H:i:s');
$stmt->execute([
    ':device_id' => $newDeviceId,
    ':display_name' => $displayName,
    ':temp_min' => $tempMin,
    ':temp_max' => $tempMax,
    ':check_interval' => $checkInterval,
    ':telegram_enabled' => $telegramEnabled,
    ':updated_at' => $updatedAt,
]);

json_response(200, [
    'config' => [
        'device_id' => $newDeviceId,
        'display_name' => $displayName,
        'temp_min' => $tempMin,
        'temp_max' => $tempMax,
        'check_interval' => $checkInterval,
        'telegram_enabled' => $telegramEnabled,
        'updated_at' => $updatedAt,
    ],
]);
