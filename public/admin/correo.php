<?php
/** Vista de un correo guardado: cómo se ve, versión de texto, estado y reenvío. */
require __DIR__ . '/../../app/admin/bootstrap.php';
require_once APP_DIR . '/correo.php';
$yo = admin_requerido();

$c = q_one('SELECT * FROM correos WHERE id = ?', [param_int('id')]);
if (!$c) {
    flash('error', 'Ese correo no existe.');
    redirect(admin_url('correos.php'));
}
$pedidoId = null;
if (str_starts_with($c['tipo'], 'pedido_') && ctype_digit((string) $c['referencia'])) {
    $pedidoId = q_val('SELECT id FROM pedidos WHERE id = ?', [(int) $c['referencia']]) ?: null;
}
$arrepId = null;
if (str_starts_with($c['tipo'], 'arrepentimiento_') && $c['referencia']) {
    $arrepId = q_val('SELECT id FROM arrepentimientos WHERE codigo = ?', [$c['referencia']]) ?: null;
}

render_admin('correo', ['c' => $c, 'pedidoId' => $pedidoId, 'arrepId' => $arrepId, 'configurado' => correo_configurado()], $c['asunto'], 'correos');
