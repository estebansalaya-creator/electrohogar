<?php
/**
 * Control de salud para Railway (u otro monitor): responde 200 si PHP y la base funcionan, 503 si no.
 * No muestra ningún dato.
 */
require __DIR__ . '/../app/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
try {
    q_val('SELECT 1');
    echo 'ok';
} catch (Throwable $ex) {
    http_response_code(503);
    error_log('[Carlos Confort] Control de salud: la base no responde: ' . $ex->getMessage());
    echo 'sin base';
}
