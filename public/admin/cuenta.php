<?php
require __DIR__ . '/../../app/admin/bootstrap.php';
$yo = admin_requerido();
admin_verificar_post();

$errores = [];
$nombre = $yo['nombre'];

if (es_post()) {
    $nombre = mb_substr(param('nombre'), 0, 80);
    $actual = (string) ($_POST['actual'] ?? '');
    $nueva = (string) ($_POST['nueva'] ?? '');
    $nueva2 = (string) ($_POST['nueva2'] ?? '');
    $hash = (string) q_val('SELECT password_hash FROM admin_usuarios WHERE id = ?', [$yo['id']]);

    if (mb_strlen($nombre) < 2) {
        $errores[] = 'Escribí tu nombre.';
    }
    if (!password_verify($actual, $hash)) {
        $errores[] = 'La contraseña actual no es correcta.';
    }
    if ($nueva !== '' && ($msg = admin_validar_clave($nueva, $nueva2))) {
        $errores[] = $msg;
    }
    if (!$errores) {
        q('UPDATE admin_usuarios SET nombre = ? WHERE id = ?', [$nombre, $yo['id']]);
        if ($nueva !== '') {
            q('UPDATE admin_usuarios SET password_hash = ? WHERE id = ?', [password_hash($nueva, PASSWORD_DEFAULT), $yo['id']]);
            session_regenerate_id(true);
        }
        flash('ok', $nueva !== '' ? 'Guardamos tus datos y la contraseña nueva.' : 'Guardamos tus datos.');
        redirect(admin_url('cuenta.php'));
    }
}

render_admin('cuenta', compact('errores', 'nombre'), 'Mi cuenta', 'cuenta');
