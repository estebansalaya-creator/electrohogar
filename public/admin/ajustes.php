<?php
/**
 * Datos de la tienda editables (se guardan en la tabla "ajustes" y tienen prioridad sobre app/config.php).
 */
require __DIR__ . '/../../app/admin/bootstrap.php';
$yo = admin_requerido();
admin_verificar_post();

$campos = [
    'tienda.nombre'                  => ['Nombre de la tienda', 'texto', 80, null],
    'tienda.whatsapp'                => ['WhatsApp', 'whatsapp', 20, 'Con código de país y área, sin el 15: 5491123456789. A este número llegan los pedidos.'],
    'tienda.telefono'                => ['Teléfono', 'texto', 40, 'Como se muestra en la página: (011) 4567-8900'],
    'tienda.email'                   => ['Email de contacto', 'email', 190, null],
    'tienda.direccion'               => ['Dirección del local', 'texto', 190, null],
    'tienda.horario'                 => ['Horario de atención', 'texto', 190, 'Ej: Lunes a viernes de 9 a 18 h. Sábados de 9 a 13 h.'],
    'tienda.instagram'               => ['Instagram (dirección completa)', 'url', 190, 'Ej: https://www.instagram.com/carlosconfort'],
    'tienda.razon_social'            => ['Razón social', 'texto', 120, 'Aparece en el pie de la página.'],
    'tienda.cuit'                    => ['CUIT', 'cuit', 13, 'Ej: 30-12345678-9'],
    'ventas.descuento_transferencia' => ['Descuento por transferencia (%)', 'porcentaje', 5, 'Con 0 no se muestra el precio con transferencia.'],
    'ventas.max_unidades'            => ['Máximo de unidades por producto en el carrito', 'entero', 3, null],
];

$errores = [];
$valores = [];
foreach ($campos as $clave => $_) {
    $valores[$clave] = (string) config($clave, '');
}

if (es_post()) {
    foreach ($campos as $clave => [$label, $tipo, $max]) {
        $v = trim((string) ($_POST[str_replace('.', '__', $clave)] ?? ''));
        switch ($tipo) {
            case 'whatsapp':
                $v = preg_replace('/\D+/', '', $v) ?? '';
                if ($v !== '' && (strlen($v) < 10 || strlen($v) > 15)) {
                    $errores[$clave] = 'El WhatsApp tiene que tener entre 10 y 15 números, con el código de país (54).';
                }
                break;
            case 'email':
                if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    $errores[$clave] = 'No es un email válido.';
                }
                break;
            case 'url':
                if ($v !== '' && !preg_match('#^https?://#i', $v)) {
                    $errores[$clave] = 'Tiene que empezar con https://';
                }
                break;
            case 'cuit':
                $digitos = preg_replace('/\D+/', '', $v) ?? '';
                if ($v !== '' && strlen($digitos) !== 11) {
                    $errores[$clave] = 'El CUIT tiene 11 números.';
                } elseif ($v !== '') {
                    $v = substr($digitos, 0, 2) . '-' . substr($digitos, 2, 8) . '-' . substr($digitos, 10, 1);
                }
                break;
            case 'porcentaje':
                $n = parse_numero($v);
                if ($v !== '' && ($n === null || $n < 0 || $n > 50)) {
                    $errores[$clave] = 'Tiene que ser un número entre 0 y 50.';
                } elseif ($v !== '') {
                    $v = (string) $n;
                }
                break;
            case 'entero':
                if ($v !== '' && (!ctype_digit($v) || (int) $v < 1 || (int) $v > 99)) {
                    $errores[$clave] = 'Tiene que ser un número entre 1 y 99.';
                }
                break;
        }
        if (mb_strlen($v) > $max && !isset($errores[$clave])) {
            $errores[$clave] = "Puede tener hasta $max caracteres.";
        }
        $valores[$clave] = $v;
    }
    if (!$errores) {
        foreach ($valores as $clave => $v) {
            if ($v === '') {
                q('DELETE FROM ajustes WHERE clave = ?', [$clave]);   // vuelve al valor de app/config.php
            } else {
                q('INSERT INTO ajustes (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [$clave, $v]);
            }
        }
        flash('ok', 'Guardamos los datos de la tienda.');
        redirect(admin_url('ajustes.php'));
    }
}

render_admin('ajustes', compact('campos', 'valores', 'errores'), 'Datos de la tienda', 'ajustes');
