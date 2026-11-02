<?php
/**
 * Botón de arrepentimiento (Res. SCI 424/2020 y art. 34 Ley 24.240).
 * Guarda la solicitud en la tabla "arrepentimientos" y muestra el código de trámite.
 */
require __DIR__ . '/../app/bootstrap.php';

$datos = ['nombre' => '', 'email' => '', 'telefono' => '', 'dni' => '', 'nro_pedido' => '', 'fecha_compra' => '', 'producto' => '', 'motivo' => ''];
$errores = [];
$codigo = $_SESSION['arrepentimiento_codigo'] ?? null;
unset($_SESSION['arrepentimiento_codigo']);

if (es_post()) {
    foreach ($datos as $k => $_) {
        $datos[$k] = mb_substr(param($k), 0, $k === 'motivo' ? 1000 : 190);
    }
    if (!csrf_valido()) {
        $errores['general'] = 'La sesión venció. Probá enviar el formulario de nuevo.';
    }
    if (param('sitio_web') !== '') { // campo trampa para bots
        $errores['general'] = 'No pudimos registrar la solicitud.';
    }
    if (mb_strlen($datos['nombre']) < 3) {
        $errores['nombre'] = 'Escribí tu nombre y apellido.';
    }
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Escribí un email válido: te enviamos ahí la respuesta.';
    }
    $dni = preg_replace('/\D+/', '', $datos['dni']);
    if (strlen($dni) < 7 || strlen($dni) > 11) {
        $errores['dni'] = 'Escribí tu DNI o CUIT, solo números.';
    }
    if ($datos['nro_pedido'] === '') {
        $errores['nro_pedido'] = 'Escribí el número de pedido o de factura.';
    }
    if ($datos['fecha_compra'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_compra'])) {
        $errores['fecha_compra'] = 'Elegí la fecha en el calendario.';
    }

    if (!$errores) {
        $datos['dni'] = $dni;
        $codigoNuevo = arrepentimiento_crear($datos);
        $_SESSION['arrepentimiento_codigo'] = $codigoNuevo;
        // Constancia al cliente y aviso al local
        try {
            require_once APP_DIR . '/correo.php';
            $a = q_one('SELECT * FROM arrepentimientos WHERE codigo = ?', [$codigoNuevo]);
            correo_encolar('arrepentimiento_cliente', $a['email'], "Tu solicitud de arrepentimiento $codigoNuevo", 'arrepentimiento-cliente', ['a' => $a], $codigoNuevo);
            $aviso = correo_config()['avisos'];
            if (filter_var($aviso, FILTER_VALIDATE_EMAIL)) {
                correo_encolar('arrepentimiento_local', $aviso, "Arrepentimiento $codigoNuevo - " . $a['nombre'], 'arrepentimiento-local', ['a' => $a], $codigoNuevo);
            }
        } catch (Throwable $ex) {
            error_log('[Carlos Confort] Correos de arrepentimiento ' . $codigoNuevo . ': ' . $ex->getMessage());
        }
        redirect(url('arrepentimiento.php'));
    }
}

render('arrepentimiento', compact('datos', 'errores', 'codigo'), [
    'titulo'  => 'Botón de arrepentimiento',
    'noindex' => false,
    'clase'   => 'pg-arrepentimiento',
]);
