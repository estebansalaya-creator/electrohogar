<?php
/**
 * Finalizar pedido: datos del cliente y forma de entrega. Registra el pedido y manda los avisos.
 */
require __DIR__ . '/../app/bootstrap.php';
require APP_DIR . '/pedidos.php';

$r = carrito_resumen();
if (!$r['lineas']) {
    flash('aviso', 'Tu carrito está vacío.');
    redirect(url('carrito.php'));
}

$datos = [
    'nombre' => '', 'telefono' => '', 'email' => '', 'entrega' => 'envio',
    'direccion' => '', 'localidad' => '', 'cp' => $r['cp'] ? (string) $r['cp'] : '', 'comentarios' => '',
];
$datos = array_merge($datos, $_SESSION['checkout_datos'] ?? []);   // recordamos lo que escribió antes
$errores = [];

if (es_post()) {
    foreach ($datos as $k => $_) {
        $datos[$k] = mb_substr(param($k), 0, $k === 'comentarios' ? 1000 : 190);
    }
    $datos['email'] = mb_strtolower($datos['email']);
    if (!in_array($datos['entrega'], ['envio', 'retiro'], true)) {
        $datos['entrega'] = 'envio';
    }

    if (!csrf_valido()) {
        $errores['_'] = 'La página estuvo abierta mucho tiempo. Revisá los datos y confirmá de nuevo.';
    } elseif (param('sitio_web') !== '') {
        $errores['_'] = 'No pudimos registrar el pedido.';
    } elseif (pedidos_recientes_ip() >= PEDIDO_MAX_POR_HORA) {
        $errores['_'] = 'Recibimos varios pedidos desde tu conexión en la última hora. Escribinos por WhatsApp y te ayudamos.';
    }
    if (mb_strlen($datos['nombre']) < 3) {
        $errores['nombre'] = 'Escribí tu nombre y apellido.';
    }
    if (strlen(preg_replace('/\D+/', '', $datos['telefono'])) < 8) {
        $errores['telefono'] = 'Escribí un teléfono o WhatsApp con código de área.';
    }
    if ($datos['email'] !== '' && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Revisá el email (o dejalo vacío).';
    }
    if ($datos['entrega'] === 'envio') {
        $cp = cp_normalizar($datos['cp']);
        if ($cp === null) {
            $errores['cp'] = 'Escribí tu código postal de 4 números.';
        } else {
            $datos['cp'] = (string) $cp;
            carrito_set_cp((string) $cp);   // recalcula el envío con este CP
        }
        if (mb_strlen($datos['direccion']) < 5) {
            $errores['direccion'] = 'Escribí calle, número y piso o departamento.';
        }
        if (mb_strlen($datos['localidad']) < 2) {
            $errores['localidad'] = 'Escribí la localidad.';
        }
    }
    $_SESSION['checkout_datos'] = $datos;

    if (!$errores) {
        $r = carrito_resumen();
        if (!$r['lineas']) {
            flash('error', 'Los productos de tu carrito ya no están disponibles.');
            redirect(url('carrito.php'));
        }
        $pedido = pedido_crear($datos, $r);
        $items = pedido_items((int) $pedido['id']);
        try {
            pedido_enviar_avisos($pedido, $items);
        } catch (Throwable $ex) {
            error_log('[Carlos Confort] Avisos del pedido ' . $pedido['id'] . ': ' . $ex->getMessage());
        }
        carrito_vaciar();
        unset($_SESSION['checkout_datos']);
        $_SESSION['ultimo_pedido'] = $pedido['token'];
        redirect(url('pedido.php', ['t' => $pedido['token']]));
    }
    $r = carrito_resumen();
}

render('checkout', compact('r', 'datos', 'errores'), ['titulo' => 'Finalizar pedido', 'noindex' => true, 'clase' => 'pg-checkout']);
