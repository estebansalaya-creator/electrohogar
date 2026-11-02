<?php
/** Detalle de un pedido: cambio de estado (descuenta o devuelve stock), costo de envío, notas e historial. */
require __DIR__ . '/../../app/admin/bootstrap.php';
require_once APP_DIR . '/pedidos.php';
$yo = admin_requerido();
admin_verificar_post();

$id = param_int('id');
$p = $id ? pedido_por_id($id) : null;
if (!$p) {
    flash('error', 'Ese pedido no existe.');
    redirect(admin_url('pedidos.php'));
}
$aqui = admin_url('pedido.php', ['id' => $id]);

if (es_post()) {
    switch (param('accion')) {
        case 'estado':
            $nuevo = param('estado');
            $nota = mb_substr(param('nota'), 0, 500);
            $mensaje = mb_substr(param('mensaje'), 0, 1000);
            if (!isset(PEDIDO_ESTADOS[$nuevo])) {
                flash('error', 'Elegí un estado de la lista.');
                break;
            }
            if ($nuevo === $p['estado'] && $nota === '') {
                flash('aviso', 'El pedido ya estaba en "' . pedido_estado_nombre($p) . '". Escribí una nota si querés dejar algo en el historial.');
                break;
            }
            try {
                $txt = pedido_cambiar_estado($id, $nuevo, $nota, $yo['nombre']);
                if (param('avisar') === '1' && $p['email']) {
                    $p2 = pedido_por_id($id);
                    $num = pedido_numero($p2);
                    correo_encolar('pedido_estado', $p2['email'], "Tu pedido N° $num: " . pedido_estado_nombre($p2), 'pedido-estado',
                        ['p' => $p2, 'items' => pedido_items($id), 'numero' => $num, 'mensaje' => $mensaje], $num);
                    $txt .= correo_configurado() ? ' Le mandamos un email al cliente.' : ' El email al cliente quedó guardado en Correos (todavía no hay casilla configurada).';
                }
                $negativos = q_all('SELECT p.nombre, p.stock FROM pedido_items i JOIN productos p ON p.id = i.producto_id WHERE i.pedido_id = ? AND p.stock < 0', [$id]);
                flash('ok', $txt);
                foreach ($negativos as $n) {
                    flash('aviso', '"' . $n['nombre'] . '" quedó con stock ' . (int) $n['stock'] . '. Revisá el stock real del producto.');
                }
            } catch (Throwable $ex) {
                error_log('[Carlos Confort] Cambio de estado del pedido ' . $id . ': ' . $ex->getMessage());
                flash('error', 'No pudimos cambiar el estado. Probá de nuevo.');
            }
            break;

        case 'envio':
            $v = trim(param('costo_envio'));
            $costo = $v === '' ? null : parse_numero($v);
            if ($v !== '' && ($costo === null || $costo < 0)) {
                flash('error', 'El costo de envío tiene que ser un número (0 si es gratis). Dejalo vacío si todavía está a cotizar.');
                break;
            }
            // Los productos mantienen el precio y el descuento con los que se hizo el pedido; solo cambia el envío.
            $anterior = (float) ($p['costo_envio'] ?? 0);
            $neto = (float) $p['total'] - $anterior;
            $netoTransf = (float) $p['total_transferencia'] - $anterior;
            $nuevoCosto = $costo ?? 0.0;
            q('UPDATE pedidos SET costo_envio = ?, total = ?, total_transferencia = ? WHERE id = ?',
                [$costo, $neto + $nuevoCosto, $netoTransf + $nuevoCosto, $id]);
            q('INSERT INTO pedido_eventos (pedido_id, estado, nota, usuario) VALUES (?, NULL, ?, ?)',
                [$id, 'Envío: ' . ($costo === null ? 'a cotizar' : ($costo == 0 ? 'gratis' : money($costo))), $yo['nombre']]);
            flash('ok', 'Guardamos el costo de envío y actualizamos el total.');
            break;

        case 'notas':
            q('UPDATE pedidos SET notas_internas = ? WHERE id = ?', [mb_substr(param('notas_internas'), 0, 2000) ?: null, $id]);
            flash('ok', 'Guardamos las notas.');
            break;
    }
    redirect($aqui);
}

$items = pedido_items($id);
$eventos = pedido_eventos($id);
$numero = pedido_numero($p);
$correos = q_all('SELECT id, tipo, para, asunto, estado, created_at, enviado_at FROM correos WHERE referencia = ? ORDER BY id DESC', [$numero]);
$waCliente = telefono_whatsapp($p['telefono']);

render_admin('pedido', compact('p', 'items', 'eventos', 'numero', 'correos', 'waCliente'), 'Pedido N° ' . $numero, 'pedidos');
