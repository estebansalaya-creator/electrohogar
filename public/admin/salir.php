<?php
require __DIR__ . '/../../app/admin/bootstrap.php';

if (es_post() && csrf_valido()) {
    admin_cerrar_sesion();
    flash('ok', 'Cerraste la sesión.');
}
redirect(admin_url('login.php'));
