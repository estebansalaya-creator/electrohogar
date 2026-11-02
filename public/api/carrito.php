<?php
/**
 * API JSON del carrito.
 *   GET  api/carrito.php                    -> estado del carrito
 *   POST api/carrito.php accion=agregar|actualizar|quitar|vaciar|cupon|quitar_cupon|envio
 * Requiere token CSRF (campo "csrf" o encabezado X-CSRF-Token) en los POST.
 */
define('API_REQUEST', true);
require __DIR__ . '/../../app/bootstrap.php';

if (!es_post()) {
    json_response(['ok' => true, 'carrito' => carrito_json()]);
}
if (!csrf_valido()) {
    json_response(['ok' => false, 'mensaje' => 'La sesión venció. Recargá la página y probá de nuevo.'], 419);
}

$id = param_int('id');
$r = match (param('accion')) {
    'agregar'      => carrito_agregar($id, max(1, param_int('cantidad', 1))),
    'actualizar'   => carrito_actualizar($id, param_int('cantidad', 1)),
    'quitar'       => carrito_quitar($id),
    'vaciar'       => (function () { carrito_vaciar(); return ['ok' => true, 'mensaje' => 'Vaciamos el carrito.']; })(),
    'cupon'        => carrito_aplicar_cupon(param('cupon')),
    'quitar_cupon' => carrito_quitar_cupon(),
    'envio'        => carrito_set_cp(param('cp')),
    default        => ['ok' => false, 'mensaje' => 'Acción desconocida.'],
};

json_response([
    'ok'       => $r['ok'],
    'mensaje'  => $r['mensaje'],
    'agregado' => isset($r['producto']) ? (int) $r['producto']['id'] : null,
    'carrito'  => carrito_json(),
], $r['ok'] ? 200 : 422);
