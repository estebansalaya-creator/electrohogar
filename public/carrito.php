<?php
/**
 * Página del carrito. Los formularios funcionan sin JavaScript (POST + redirect);
 * con JavaScript, agregar al carrito usa api/carrito.php.
 */
require __DIR__ . '/../app/bootstrap.php';

if (es_post()) {
    if (!csrf_valido()) {
        flash('error', 'La sesión venció. Probá de nuevo.');
        redirect(url('carrito.php'));
    }
    $accion = param('accion');
    $id = param_int('id');
    $r = match ($accion) {
        'agregar'      => carrito_agregar($id, max(1, param_int('cantidad', 1))),
        'actualizar'   => carrito_actualizar($id, param_int('cantidad', 1)),
        'quitar'       => carrito_quitar($id),
        'vaciar'       => (function () { carrito_vaciar(); return ['ok' => true, 'mensaje' => 'Vaciamos el carrito.']; })(),
        'cupon'        => carrito_aplicar_cupon(param('cupon')),
        'quitar_cupon' => carrito_quitar_cupon(),
        'envio'        => carrito_set_cp(param('cp')),
        default        => ['ok' => false, 'mensaje' => 'No entendimos qué querías hacer.'],
    };
    flash($r['ok'] ? 'ok' : 'error', $r['mensaje']);
    redirect(url('carrito.php'));
}

$resumen = carrito_resumen();
foreach ($resumen['avisos'] as $aviso) {
    flash('aviso', $aviso);
}

render('carrito', [
    'r'         => $resumen,
    'sugeridos' => $resumen['lineas'] ? [] : productos_destacados(5),
], ['titulo' => 'Carrito', 'noindex' => true, 'clase' => 'pg-carrito']);
