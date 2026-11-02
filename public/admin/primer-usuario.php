<?php
/**
 * Alta del primer usuario del panel. Solo funciona:
 *  - si todavía no hay ningún usuario, y
 *  - desde la misma PC donde está instalado el sitio (http://localhost...).
 */
require __DIR__ . '/../../app/admin/bootstrap.php';

if (admin_hay_usuarios()) {
    redirect(admin_url('login.php'));
}

$permitido = es_localhost();
$errores = [];
$datos = ['nombre' => '', 'email' => ''];

if ($permitido && es_post()) {
    $datos = ['nombre' => mb_substr(param('nombre'), 0, 80), 'email' => mb_strtolower(param('email'))];
    $clave = (string) ($_POST['clave'] ?? '');
    $clave2 = (string) ($_POST['clave2'] ?? '');
    if (!csrf_valido()) {
        $errores[] = 'La página estuvo abierta mucho tiempo. Probá de nuevo.';
    }
    if (mb_strlen($datos['nombre']) < 2) {
        $errores[] = 'Escribí tu nombre.';
    }
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Escribí un email válido.';
    }
    if ($msg = admin_validar_clave($clave, $clave2)) {
        $errores[] = $msg;
    }
    if (!$errores && !admin_hay_usuarios()) {
        q('INSERT INTO admin_usuarios (nombre, email, password_hash, principal) VALUES (?, ?, ?, 1)',
            [$datos['nombre'], $datos['email'], password_hash($clave, PASSWORD_DEFAULT)]);
        $u = q_one('SELECT * FROM admin_usuarios WHERE email = ?', [$datos['email']]);
        admin_iniciar_sesion($u);
        flash('ok', 'Listo, ya podés administrar la tienda.');
        redirect(admin_url());
    }
}

render_admin('primer-usuario', compact('permitido', 'errores', 'datos'), 'Crear el primer usuario');
