<?php
/**
 * Crea un usuario del panel o le cambia la contraseña si ya existe.
 *
 *   php herramientas/crear_admin.php tu@email.com "Tu nombre"
 *   php herramientas/crear_admin.php tu@email.com --principal
 *
 * Pide la contraseña dos veces (mínimo 10 caracteres).
 * --principal: lo deja como administrador principal (el único que puede cambiar las credenciales de
 * Mercado Pago); el que lo era deja de serlo. Sin cambiar la contraseña si el usuario ya existe.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo desde la línea de comandos.');
}

require __DIR__ . '/../app/admin/bootstrap.php';   // incluye app/bootstrap.php

$args = array_slice($argv, 1);
$hacerPrincipal = in_array('--principal', $args, true);
$args = array_values(array_filter($args, fn($a) => $a !== '--principal'));
$email = mb_strtolower(trim((string) ($args[0] ?? '')));
$nombre = trim((string) ($args[1] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php herramientas/crear_admin.php tu@email.com \"Tu nombre\"\n");
    exit(1);
}

$existente = q_one('SELECT id, nombre FROM admin_usuarios WHERE email = ?', [$email]);
if (!$existente && $nombre === '') {
    fwrite(STDERR, "Para un usuario nuevo indicá también el nombre: php herramientas/crear_admin.php $email \"Tu nombre\"\n");
    exit(1);
}

function marcar_principal(int $id, string $email): void
{
    db()->beginTransaction();
    q('UPDATE admin_usuarios SET principal = 0 WHERE id <> ?', [$id]);
    q('UPDATE admin_usuarios SET principal = 1 WHERE id = ?', [$id]);
    q("INSERT INTO admin_registro (usuario_id, usuario, accion, detalle, ip) VALUES (?, 'Consola', 'principal', ?, 'consola')", [$id, "Nuevo administrador principal: $email"]);
    db()->commit();
}

// Solo pasar a principal un usuario existente: no hace falta la contraseña
if ($existente && $hacerPrincipal && $nombre === '') {
    marcar_principal((int) $existente['id'], $email);
    echo "Listo: $email es ahora el administrador principal.\n";
    exit(0);
}

function leer(string $pregunta): string
{
    echo $pregunta;
    return rtrim((string) fgets(STDIN), "\r\n");
}

$clave = leer($existente ? "Nueva contraseña para $email: " : "Contraseña para $email: ");
$clave2 = leer('Repetila: ');
if ($msg = admin_validar_clave($clave, $clave2)) {
    fwrite(STDERR, $msg . "\n");
    exit(1);
}

$hash = password_hash($clave, PASSWORD_DEFAULT);
if ($existente) {
    q('UPDATE admin_usuarios SET password_hash = ?, activo = 1' . ($nombre !== '' ? ', nombre = ?' : '') . ' WHERE id = ?',
        $nombre !== '' ? [$hash, $nombre, $existente['id']] : [$hash, $existente['id']]);
    q('DELETE FROM admin_intentos WHERE email = ?', [$email]);
    echo "Listo: cambiamos la contraseña de $email.\n";
} else {
    $sinPrincipal = !(int) q_val('SELECT COUNT(*) FROM admin_usuarios WHERE principal = 1');
    q('INSERT INTO admin_usuarios (nombre, email, password_hash) VALUES (?, ?, ?)', [$nombre, $email, $hash]);
    $existente = q_one('SELECT id FROM admin_usuarios WHERE email = ?', [$email]);
    $hacerPrincipal = $hacerPrincipal || $sinPrincipal;
    echo "Listo: creamos el usuario $email. Entrá en /admin con esa contraseña.\n";
}
if ($hacerPrincipal) {
    marcar_principal((int) $existente['id'], $email);
    echo "$email es el administrador principal.\n";
}
