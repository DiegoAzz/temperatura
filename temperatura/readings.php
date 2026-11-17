<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

date_default_timezone_set('America/Bogota');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    json_response(204, []);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    $limit = max(1, min($limit, 500));

    $stmt = db()->prepare(
        'SELECT id, device_id, temperature, temp_min, temp_max, status, created_at
         FROM temperature_readings
         ORDER BY id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    json_response(200, ['readings' => $stmt->fetchAll()]);
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

$deviceId = trim((string) ($payload['device_id'] ?? 'esp8266-sysnet-01'));
$temperature = isset($payload['temperature']) ? (float) $payload['temperature'] : null;
$tempMin = isset($payload['temp_min']) ? (float) $payload['temp_min'] : null;
$tempMax = isset($payload['temp_max']) ? (float) $payload['temp_max'] : null;

if ($deviceId === '' || $temperature === null || $tempMin === null || $tempMax === null) {
    json_response(400, ['error' => 'Faltan campos requeridos']);
}

if ($temperature < $tempMin) {
    $status = 'LOW';
} elseif ($temperature > $tempMax) {
    $status = 'HIGH';
} else {
    $status = 'OK';
}

$stmt = db()->prepare(
    'INSERT INTO temperature_readings
        (device_id, temperature, temp_min, temp_max, status, created_at)
     VALUES
        (:device_id, :temperature, :temp_min, :temp_max, :status, :created_at)'
);
$stmt->execute([
    ':device_id' => $deviceId,
    ':temperature' => $temperature,
    ':temp_min' => $tempMin,
    ':temp_max' => $tempMax,
    ':status' => $status,
    ':created_at' => date('Y-m-d H:i:s'),
]);

json_response(201, [
    'id' => (int) db()->lastInsertId(),
    'device_id' => $deviceId,
    'temperature' => $temperature,
    'temp_min' => $tempMin,
    'temp_max' => $tempMax,
    'status' => $status,
    'created_at' => date('Y-m-d H:i:s'),
]);
