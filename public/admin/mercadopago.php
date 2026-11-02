<?php
/**
 * Credenciales de Mercado Pago. Solo el administrador principal puede ver y cambiar esta sección.
 * Cada cambio pide la contraseña, queda registrado y se avisa por email.
 */
require __DIR__ . '/../../app/admin/bootstrap.php';
require_once APP_DIR . '/correo.php';
require_once APP_DIR . '/mercadopago.php';
$yo = admin_principal_requerido();
admin_verificar_post();

$aqui = admin_url('mercadopago.php');
$cfg = mp_config();
$errores = [];

/** Guarda (o borra, con null) una clave mp.* en ajustes. */
$guardarAjuste = function (string $campo, ?string $valor): void {
    if ($valor === null || $valor === '') {
        q('DELETE FROM ajustes WHERE clave = ?', ['mp.' . $campo]);
    } else {
        q('INSERT INTO ajustes (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', ['mp.' . $campo, $valor]);
    }
};

/** Aviso por email al principal y al email de avisos de la tienda (si es otro). */
$avisar = function (string $que, array $detalle) use ($yo): void {
    $destinos = array_unique(array_filter([$yo['email'], correo_config()['avisos']], fn($d) => filter_var($d, FILTER_VALIDATE_EMAIL)));
    foreach ($destinos as $para) {
        correo_encolar('seguridad', $para, 'Aviso de seguridad: ' . $que, 'aviso-seguridad', [
            'titulo'  => $que,
            'detalle' => $detalle + [
                'Quién'  => $yo['nombre'] . ' (' . $yo['email'] . ')',
                'Cuándo' => date('d/m/Y H:i'),
                'Desde'  => ip_cliente(),
            ],
            'nota' => 'Si no fuiste vos, entrá al panel, cambiá tu contraseña y revisá las credenciales de Mercado Pago.',
        ], 'mercadopago');
    }
};

if (es_post()) {
    $accion = param('accion');

    if ($accion === 'verificar') {
        if (!$cfg['access_token']) {
            flash('error', 'No hay un Access Token guardado.');
        } else {
            $v = mp_verificar_token($cfg['access_token']);
            if ($v['ok']) {
                $guardarAjuste('cuenta_id', $v['id']);
                $guardarAjuste('cuenta_nombre', $v['apodo']);
                $guardarAjuste('cuenta_email', $v['email']);
                $guardarAjuste('verificado', date('Y-m-d H:i:s'));
                flash('ok', 'Las credenciales funcionan: corresponden a la cuenta ' . ($v['apodo'] ?: $v['id']) . '.');
            } else {
                flash('error', $v['error']);
            }
        }
        redirect($aqui);
    }

    if ($accion === 'guardar' || $accion === 'borrar') {
        if ($p = cifrado_problema()) {
            flash('error', $p);
            redirect($aqui);
        }
        if ($msg = admin_confirmar_clave($yo, (string) ($_POST['clave_actual'] ?? ''))) {
            $errores['clave_actual'] = $msg;
        }
    }

    if ($accion === 'guardar' && !$errores) {
        $token = trim((string) ($_POST['access_token'] ?? ''));
        $secreto = trim((string) ($_POST['webhook_secret'] ?? ''));
        if ($token === '' && $secreto === '') {
            $errores['access_token'] = 'Pegá el Access Token o la clave secreta que querés cambiar.';
        }
        if ($token !== '' && !mp_token_valido_formato($token)) {
            $errores['access_token'] = 'No parece un Access Token: empieza con APP_USR- (o TEST- en las de prueba). Copialo completo, sin espacios.';
        }
        if ($secreto !== '' && !preg_match('/^[A-Za-z0-9_-]{16,200}$/', $secreto)) {
            $errores['webhook_secret'] = 'La clave secreta tiene solo letras y números. Copiala completa, sin espacios.';
        }
        if (!$cfg['access_token'] && $token === '' && !$errores) {
            $errores['access_token'] = 'Primero cargá el Access Token.';
        }
        $v = null;
        if ($token !== '' && !$errores) {
            $v = mp_verificar_token($token);
            if (!$v['ok']) {
                $errores['access_token'] = $v['error'] . ' No guardamos nada.';
            } elseif ($v['pais'] !== '' && $v['pais'] !== 'MLA') {
                $errores['access_token'] = 'Ese Access Token es de una cuenta de otro país (' . $v['pais'] . '). La tienda cobra en pesos argentinos.';
            }
        }
        if (!$errores) {
            $cambios = [];
            $detalle = [];
            if ($token !== '') {
                $guardarAjuste('access_token', cifrar($token));
                $guardarAjuste('cuenta_id', $v['id']);
                $guardarAjuste('cuenta_nombre', $v['apodo']);
                $guardarAjuste('cuenta_email', $v['email']);
                $guardarAjuste('verificado', date('Y-m-d H:i:s'));
                $cambios[] = 'Access Token (' . enmascarar($token) . ', cuenta ' . ($v['apodo'] ?: $v['id']) . ($v['prueba'] ? ', de prueba' : '') . ')';
                $detalle['Cuenta de Mercado Pago'] = trim(($v['apodo'] ?: '') . ' ' . ($v['email'] ? '<' . $v['email'] . '>' : '') . ' (N° ' . $v['id'] . ')');
                $detalle['Access Token'] = enmascarar($token) . ($v['prueba'] ? ' (de prueba)' : '');
                if ($cfg['cuenta_id'] !== '' && $cfg['cuenta_id'] !== $v['id']) {
                    $detalle['Atención'] = 'Es una cuenta distinta de la anterior (' . ($cfg['cuenta_nombre'] ?: $cfg['cuenta_id']) . '): los cobros van a ir a esta cuenta nueva.';
                }
            }
            if ($secreto !== '') {
                $guardarAjuste('webhook_secret', cifrar($secreto));
                $cambios[] = 'clave secreta de notificaciones';
                $detalle['Clave secreta de notificaciones'] = 'cambiada';
            }
            admin_registrar($yo, 'mercadopago', 'Cambió: ' . implode(' y ', $cambios));
            $avisar('cambiaron las credenciales de Mercado Pago', $detalle);
            flash('ok', 'Guardamos las credenciales' . ($v ? ': corresponden a la cuenta ' . ($v['apodo'] ?: $v['id']) . ($v['prueba'] ? ' (de prueba)' : '') : '') . '. Te mandamos un aviso por email.');
            redirect($aqui);
        }
    }

    if ($accion === 'borrar' && !$errores) {
        foreach (['access_token', 'webhook_secret', 'cuenta_id', 'cuenta_nombre', 'cuenta_email', 'verificado'] as $campo) {
            $guardarAjuste($campo, null);
        }
        admin_registrar($yo, 'mercadopago', 'Borró las credenciales');
        $avisar('se borraron las credenciales de Mercado Pago', ['Qué pasó' => 'La tienda ya no puede cobrar con Mercado Pago hasta que se carguen de nuevo.']);
        flash('ok', 'Borramos las credenciales de Mercado Pago.');
        redirect($aqui);
    }
}

$historial = q_all("SELECT usuario, detalle, ip, created_at FROM admin_registro WHERE accion = 'mercadopago' ORDER BY id DESC LIMIT 10");
$tokenIlegible = $cfg['token_guardado'] && $cfg['access_token'] === '';
$secretoIlegible = $cfg['secreto_guardado'] && $cfg['webhook_secret'] === '';

render_admin('mercadopago', [
    'cfg'             => $cfg,
    'errores'         => $errores,
    'historial'       => $historial,
    'problemaCifrado' => cifrado_problema(),
    'tokenIlegible'   => $tokenIlegible,
    'secretoIlegible' => $secretoIlegible,
    'accion'          => es_post() ? param('accion') : '',
], 'Mercado Pago', 'mercadopago');
