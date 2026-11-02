<?php
require __DIR__ . '/../../app/admin/bootstrap.php';
require APP_DIR . '/admin/crud.php';
$yo = admin_requerido();

if (!es_post() || !csrf_valido()) {
    flash('error', 'La página estuvo abierta mucho tiempo. Probá de nuevo.');
    redirect(admin_url());
}
$ent = entidad(param('e'));
$r = crud_borrar($ent, param_int('id'));
flash($r['ok'] ? 'ok' : 'error', $r['mensaje']);
redirect(admin_url('lista.php', ['e' => $ent['clave']]));
