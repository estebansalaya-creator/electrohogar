<?php
/**
 * Cotización de envío de un producto.
 *   GET api/envio.php?cp=1425&id=5
 */
define('API_REQUEST', true);
require __DIR__ . '/../../app/bootstrap.php';

$cp = cp_normalizar(param('cp'));
if ($cp === null) {
    json_response(['ok' => false, 'mensaje' => 'Escribí un código postal de 4 números, por ejemplo 1425.'], 422);
}
$id = param_int('id');
$productos = $id ? array_values(productos_por_ids([$id])) : [];
if ($id && !$productos) {
    json_response(['ok' => false, 'mensaje' => 'Ese producto ya no está disponible.'], 404);
}

$cot = cotizar_envio($cp, $productos);
if (!$cot['ok']) {
    json_response($cot, 200);
}

// Guardamos el CP para precargarlo en el carrito
$c = &carrito_sesion();
$c['cp'] = $cp;

$cot['mensaje'] = $cot['zona'] . ': ' . ($cot['gratis'] ? 'envío gratis' : 'envío ' . money($cot['costo'])) . '. Llega en ' . $cot['dias'] . '.';
json_response($cot);
