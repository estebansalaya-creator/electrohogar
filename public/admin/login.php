<?php
require __DIR__ . '/../../app/admin/bootstrap.php';

if (!admin_hay_usuarios()) {
    redirect(admin_url('primer-usuario.php'));
}
if (admin_actual()) {
    redirect(admin_url());
}

$error = null;
$email = '';
$volver = param('volver');

if (es_post()) {
    $email = mb_strtolower(param('email'));
    $clave = (string) ($_POST['clave'] ?? '');
    if (!csrf_valido()) {
        $error = 'La página estuvo abierta mucho tiempo. Probá de nuevo.';
    } elseif (admin_bloqueado()) {
        $error = 'Hubo demasiados intentos fallidos. Esperá ' . ADMIN_VENTANA_INTENTOS . ' minutos y probá de nuevo.';
    } else {
        $u = q_one('SELECT * FROM admin_usuarios WHERE email = ? AND activo = 1', [$email]);
        if ($u && password_verify($clave, $u['password_hash'])) {
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                q('UPDATE admin_usuarios SET password_hash = ? WHERE id = ?', [password_hash($clave, PASSWORD_DEFAULT), $u['id']]);
            }
            admin_iniciar_sesion($u);
            redirect(admin_destino($volver));
        }
        admin_registrar_intento($email);
        $error = 'El email o la contraseña no son correctos.';
    }
}

render_admin('login', compact('error', 'email', 'volver'), 'Ingresar');
