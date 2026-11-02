<?php
/**
 * Reintenta los correos pendientes o con error (de los últimos 3 días, hasta 5 intentos cada uno).
 * Pensado para el Programador de tareas de Windows, cada 10 minutos:
 *   php D:\electro\herramientas\enviar_correos.php
 * Si no hay casilla configurada no hace nada.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';
require_once APP_DIR . '/correo.php';

if (!correo_configurado()) {
    fwrite(STDOUT, "No hay casilla de correo configurada (Panel > Correos). No se envió nada.\n");
    exit(0);
}
[$ok, $mal] = correo_procesar_pendientes((int) ($argv[1] ?? 30));
fwrite(STDOUT, date('Y-m-d H:i') . " - enviados: $ok, con error: $mal\n");
exit($mal ? 1 : 0);
