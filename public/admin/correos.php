<?php
/**
 * Correos: datos de la casilla (SMTP), envío de prueba y bandeja de salida.
 * Mientras no haya casilla configurada, los correos se guardan igual y se pueden leer acá.
 */
require __DIR__ . '/../../app/admin/bootstrap.php';
require_once APP_DIR . '/correo.php';
$yo = admin_requerido();
admin_verificar_post();

$aqui = admin_url('correos.php', array_filter(['estado' => param('estado'), 'pagina' => param('pagina')]));
$cfg = correo_config();
$errores = [];
$valores = [
    'host'             => (string) config('correo.host', ''),
    'puerto'           => (string) (config('correo.puerto', '') ?: ''),
    'seguridad'        => $cfg['seguridad'],
    'usuario'          => (string) config('correo.usuario', ''),
    'remitente'        => (string) config('correo.remitente', ''),
    'remitente_nombre' => (string) config('correo.remitente_nombre', ''),
    'avisos'           => (string) config('correo.avisos', ''),
    'verificar_ssl'    => $cfg['verificar_ssl'] ? '1' : '0',
];
$hayClave = $cfg['clave'] !== '';
$claveIlegible = !$hayClave && (string) config('correo.clave', '') !== '';   // cifrada con otra APP_KEY

if (es_post()) {
    switch (param('accion')) {
        case 'config':
            foreach (array_keys($valores) as $k) {
                $valores[$k] = trim((string) ($_POST[$k] ?? ''));
            }
            $valores['verificar_ssl'] = param('verificar_ssl') === '1' ? '1' : '0';
            if ($valores['host'] !== '' && !preg_match('/^[a-z0-9.-]+$/i', $valores['host'])) {
                $errores['host'] = 'Escribí solo el nombre del servidor, por ejemplo smtp.gmail.com (sin https:// ni barras).';
            }
            if ($valores['puerto'] !== '' && (!ctype_digit($valores['puerto']) || (int) $valores['puerto'] < 1 || (int) $valores['puerto'] > 65535)) {
                $errores['puerto'] = 'El puerto es un número: 465 (SSL) o 587 (TLS).';
            }
            if (!in_array($valores['seguridad'], ['ssl', 'tls', 'ninguna'], true)) {
                $errores['seguridad'] = 'Elegí una opción.';
            }
            foreach (['remitente', 'avisos'] as $k) {
                if ($valores[$k] !== '' && !filter_var($valores[$k], FILTER_VALIDATE_EMAIL)) {
                    $errores[$k] = 'No es un email válido.';
                }
            }
            if ($valores['host'] !== '' && $valores['remitente'] === '' && !filter_var($valores['usuario'], FILTER_VALIDATE_EMAIL)) {
                $errores['remitente'] = 'Escribí desde qué email salen los correos (normalmente el mismo de la casilla).';
            }
            foreach ($valores as $k => $v) {
                if (mb_strlen($v) > 190) {
                    $errores[$k] = 'Es demasiado largo.';
                }
            }
            if ($errores) {
                break;
            }
            $guardar = $valores;
            $clave = (string) ($_POST['clave'] ?? '');
            if (param('borrar_clave') === '1') {
                $guardar['clave'] = '';
            } elseif ($clave !== '') {
                $guardar['clave'] = cifrado_disponible() ? cifrar($clave) : $clave;   // cifrada si hay APP_KEY
            }
            foreach ($guardar as $k => $v) {
                $clave_ajuste = 'correo.' . $k;
                if ($v === '' || ($k === 'verificar_ssl' && $v === '1')) {
                    q('DELETE FROM ajustes WHERE clave = ?', [$clave_ajuste]);   // vuelve a lo de config.local.php / app/config.php
                } else {
                    q('INSERT INTO ajustes (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [$clave_ajuste, $v]);
                }
            }
            admin_registrar($yo, 'correo', 'Datos de la casilla: ' . ($valores['host'] ?: 'sin servidor') . (isset($guardar['clave']) ? ' (cambió la contraseña)' : ''));
            flash('ok', 'Guardamos los datos de la casilla. Mandate un correo de prueba para confirmar que funciona.');
            redirect(admin_url('correos.php'));

        case 'probar':
            $para = trim(param('para'));
            if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Escribí a qué email mandar la prueba.');
            } elseif (!correo_configurado()) {
                flash('error', 'Primero completá y guardá el servidor y el email remitente.');
            } else {
                $id = correo_encolar('prueba', $para, 'Correo de prueba - ' . tienda('nombre'), 'prueba', ['quien' => $yo['nombre']]);
                $c = q_one('SELECT estado, error FROM correos WHERE id = ?', [$id]);
                if ($c['estado'] === 'enviado') {
                    flash('ok', "Listo: el servidor aceptó el correo de prueba para $para. Revisá esa casilla (y la carpeta de spam).");
                } else {
                    flash('error', 'No se pudo enviar: ' . ($c['error'] ?: 'error desconocido.'));
                }
            }
            break;

        case 'reenviar':
            $id = param_int('id');
            if (!correo_configurado()) {
                flash('error', 'Todavía no hay casilla configurada: completá los datos de arriba.');
            } elseif (q_val('SELECT COUNT(*) FROM correos WHERE id = ?', [$id])) {
                q("UPDATE correos SET estado = 'pendiente' WHERE id = ? AND estado <> 'enviado'", [$id]);
                if (correo_enviar($id)) {
                    flash('ok', 'Enviamos el correo.');
                } else {
                    flash('error', 'No se pudo enviar: ' . (q_val('SELECT error FROM correos WHERE id = ?', [$id]) ?: 'error desconocido.'));
                }
            }
            if (param('volver') === 'correo') {
                redirect(admin_url('correo.php', ['id' => $id]));
            }
            break;

        case 'procesar':
            if (!correo_configurado()) {
                flash('error', 'Todavía no hay casilla configurada.');
                break;
            }
            [$ok, $mal] = correo_procesar_pendientes(30);
            flash($mal ? 'aviso' : 'ok', $ok + $mal === 0
                ? 'No había correos pendientes.'
                : "Enviamos $ok " . plural($ok, 'correo', 'correos') . ($mal ? " y $mal " . plural($mal, 'falló', 'fallaron') . ' (mirá el detalle en la lista).' : '.'));
            break;
    }
    if (!$errores) {
        redirect($aqui);
    }
}

// Bandeja de salida
$estado = param('estado');
$estados = ['pendiente' => 'Pendientes', 'enviado' => 'Enviados', 'error' => 'Con error', 'sin_configurar' => 'Sin enviar (sin casilla)'];
if (!isset($estados[$estado])) {
    $estado = '';
}
$where = $estado ? 'WHERE estado = ?' : '';
$params = $estado ? [$estado] : [];
$total = (int) q_val("SELECT COUNT(*) FROM correos $where", $params);
$porPagina = 30;
$paginas = max(1, (int) ceil($total / $porPagina));
$pagina = min(max(1, param_int('pagina', 1)), $paginas);
$items = q_all("SELECT id, tipo, referencia, para, asunto, estado, intentos, error, created_at, enviado_at FROM correos $where ORDER BY id DESC LIMIT $porPagina OFFSET " . (($pagina - 1) * $porPagina), $params);
$conteo = array_column(q_all('SELECT estado, COUNT(*) AS n FROM correos GROUP BY estado'), 'n', 'estado');
$configurado = correo_configurado();

render_admin('correos', compact('valores', 'errores', 'hayClave', 'claveIlegible', 'configurado', 'items', 'total', 'pagina', 'paginas', 'estado', 'estados', 'conteo', 'yo'), 'Correos', 'correos');
