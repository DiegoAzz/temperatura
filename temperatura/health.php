<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

try {
    db()->query('SELECT 1');
    json_response(200, ['ok' => true, 'database' => true]);
} catch (Throwable $e) {
    json_response(500, ['ok' => false, 'database' => false, 'error' => $e->getMessage()]);
}
