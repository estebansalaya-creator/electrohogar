<?php
/**
 * Pedidos: alta desde el carrito, estados (con descuento y devolución de stock) y avisos por correo.
 */
declare(strict_types=1);

require_once APP_DIR . '/correo.php';

const PEDIDO_ESTADOS = [
    'nuevo'      => 'Nuevo',
    'confirmado' => 'Confirmado',
    'preparando' => 'En preparación',
    'enviado'    => 'Enviado',
    'entregado'  => 'Entregado',
    'cancelado'  => 'Cancelado',
];
/** Estados en los que la mercadería ya está comprometida (se descuenta del stock). */
const PEDIDO_ESTADOS_CON_STOCK = ['confirmado', 'preparando', 'enviado', 'entregado'];
const PEDIDO_MAX_POR_HORA = 6;   // por IP, para frenar envíos automáticos

/** Nombre del estado para mostrar; "enviado" en un retiro se lee "Listo para retirar". */
function pedido_estado_nombre(array $p, ?string $estado = null): string
{
    $estado ??= $p['estado'];
    if ($estado === 'enviado' && ($p['entrega'] ?? '') === 'retiro') {
        return 'Listo para retirar';
    }
    return PEDIDO_ESTADOS[$estado] ?? $estado;
}

/**
 * Pasa un teléfono argentino escrito como sea ("011 15 2345-6789", "+54 9 351 ...") al formato de wa.me (549 + área + número).
 * Devuelve null si no se puede interpretar con seguridad.
 */
function telefono_whatsapp(string $tel): ?string
{
    $d = preg_replace('/\D+/', '', $tel) ?? '';
    if (str_starts_with($d, '00')) {
        $d = substr($d, 2);
    }
    if (str_starts_with($d, '549') && strlen($d) === 13) {
        return $d;
    }
    if (str_starts_with($d, '54') && strlen($d) === 12) {
        return '549' . substr($d, 2);
    }
    $d = ltrim($d, '0');
    if (strlen($d) === 12) {   // con el 15 después del código de área
        foreach (str_starts_with($d, '11') ? [2] : [3, 4, 2] as $area) {
            if (substr($d, $area, 2) === '15') {
                $d = substr($d, 0, $area) . substr($d, $area + 2);
                break;
            }
        }
    }
    return strlen($d) === 10 ? '549' . $d : null;
}

function pedido_numero(array $p): string
{
    return str_pad((string) $p['id'], 6, '0', STR_PAD_LEFT);
}

function pedido_por_id(int $id): ?array
{
    return q_one('SELECT * FROM pedidos WHERE id = ?', [$id]);
}

function pedido_por_token(string $token): ?array
{
    return preg_match('/^[a-f0-9]{32}$/', $token) ? q_one('SELECT * FROM pedidos WHERE token = ?', [$token]) : null;
}

function pedido_items(int $id): array
{
    return q_all('SELECT * FROM pedido_items WHERE pedido_id = ? ORDER BY id', [$id]);
}

function pedido_eventos(int $id): array
{
    return q_all('SELECT * FROM pedido_eventos WHERE pedido_id = ? ORDER BY created_at, id', [$id]);
}

function pedidos_recientes_ip(): int
{
    return (int) q_val('SELECT COUNT(*) FROM pedidos WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)', [ip_cliente()]);
}

/**
 * Crea el pedido con lo que hay en el carrito. $cliente: nombre, telefono, email, entrega, direccion, localidad, cp, comentarios
 * Los precios salen de la base (carrito_resumen), nunca del formulario.
 */
function pedido_crear(array $cliente, array $r): array
{
    if (!$r['lineas']) {
        throw new RuntimeException('El carrito está vacío.');
    }
    $productosNeto = $r['subtotal'] - $r['descuento'];
    if ($cliente['entrega'] === 'retiro') {
        $costo = 0.0;
        $zona = 'Retiro en el local';
    } elseif ($r['envio'] && $r['envio']['ok']) {
        $costo = (float) $r['envio']['costo'];
        $zona = $r['envio']['zona'];
    } else {
        $costo = null;   // a cotizar
        $zona = null;
    }
    $total = $productosNeto + ($costo ?? 0);
    $totalTransf = precio_transferencia($productosNeto) + ($costo ?? 0);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        q(
            'INSERT INTO pedidos (token, nombre, telefono, email, entrega, direccion, localidad, cp, zona, comentarios,
                                  subtotal, cupon, descuento, costo_envio, total, total_transferencia, cuotas, ip)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                bin2hex(random_bytes(16)), $cliente['nombre'], $cliente['telefono'], $cliente['email'] ?: null,
                $cliente['entrega'], $cliente['direccion'] ?: null, $cliente['localidad'] ?: null,
                $cliente['cp'] ?: null, $zona, $cliente['comentarios'] ?: null,
                $r['subtotal'], $r['cupon']['codigo'] ?? null, $r['descuento'], $costo, $total, $totalTransf,
                $r['cuotas'], ip_cliente(),
            ]
        );
        $id = (int) $pdo->lastInsertId();
        foreach ($r['lineas'] as $l) {
            $p = $l['producto'];
            q(
                'INSERT INTO pedido_items (pedido_id, producto_id, sku, nombre, marca, precio_unitario, cantidad, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$id, $p['id'], $p['sku'], $p['nombre'], $p['marca'], $p['precio'], $l['cantidad'], $l['subtotal']]
            );
        }
        q("INSERT INTO pedido_eventos (pedido_id, estado, nota, usuario) VALUES (?, 'nuevo', 'Pedido recibido desde la web', 'Cliente')", [$id]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return pedido_por_id($id);
}

/**
 * Cambia el estado. Al confirmar se descuenta el stock; al cancelar (o volver a "nuevo") se devuelve.
 * @return string mensaje para mostrar
 */
function pedido_cambiar_estado(int $id, string $nuevo, ?string $nota, string $usuario): string
{
    if (!isset(PEDIDO_ESTADOS[$nuevo])) {
        throw new RuntimeException('Estado desconocido.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $p = q_one('SELECT * FROM pedidos WHERE id = ? FOR UPDATE', [$id]);
        if (!$p) {
            throw new RuntimeException('El pedido no existe.');
        }
        $extra = '';
        $descontar = in_array($nuevo, PEDIDO_ESTADOS_CON_STOCK, true);
        if ($descontar && !(int) $p['stock_descontado']) {
            foreach (pedido_items($id) as $it) {
                if ($it['producto_id']) {
                    q('UPDATE productos SET stock = stock - ? WHERE id = ?', [$it['cantidad'], $it['producto_id']]);
                }
            }
            q('UPDATE pedidos SET stock_descontado = 1 WHERE id = ?', [$id]);
            $extra = ' Se descontó del stock.';
        } elseif (!$descontar && (int) $p['stock_descontado']) {
            foreach (pedido_items($id) as $it) {
                if ($it['producto_id']) {
                    q('UPDATE productos SET stock = stock + ? WHERE id = ?', [$it['cantidad'], $it['producto_id']]);
                }
            }
            q('UPDATE pedidos SET stock_descontado = 0 WHERE id = ?', [$id]);
            $extra = ' Se devolvió el stock.';
        }
        q('UPDATE pedidos SET estado = ? WHERE id = ?', [$nuevo, $id]);
        q('INSERT INTO pedido_eventos (pedido_id, estado, nota, usuario) VALUES (?, ?, ?, ?)',
            [$id, $nuevo === $p['estado'] ? null : $nuevo, $nota ?: null, $usuario]);
        $pdo->commit();
        return ($nuevo === $p['estado'] ? 'Guardamos la nota.' : 'El pedido pasó a "' . PEDIDO_ESTADOS[$nuevo] . '".') . $extra;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/** Texto del pedido para mandarlo por WhatsApp. */
function pedido_texto_whatsapp(array $p, array $items): string
{
    $t = 'Hola ' . tienda('nombre') . ', soy ' . $p['nombre'] . '. Te envío mi pedido N° ' . pedido_numero($p) . ":\n\n";
    foreach ($items as $it) {
        $t .= '• ' . $it['cantidad'] . ' x ' . $it['nombre'] . ($it['marca'] ? ' (' . $it['marca'] . ')' : '') . ' - ' . money($it['subtotal']) . "\n";
    }
    if ($p['cupon']) {
        $t .= "\nCupón " . $p['cupon'] . ': -' . money($p['descuento']);
    }
    $t .= "\n" . pedido_texto_entrega($p);
    $t .= "\nTotal: " . money($p['total']) . ($p['costo_envio'] === null && $p['entrega'] === 'envio' ? ' + envío' : '');
    $t .= "\nCon transferencia: " . money($p['total_transferencia']);
    $t .= "\n\n¿Me confirman stock y forma de pago?";
    return $t;
}

function pedido_texto_entrega(array $p): string
{
    if ($p['entrega'] === 'retiro') {
        return 'Entrega: retiro en el local';
    }
    $dir = trim(implode(', ', array_filter([$p['direccion'], $p['localidad'], $p['cp'] ? 'CP ' . $p['cp'] : null])));
    $costo = $p['costo_envio'] === null ? 'a cotizar' : ((float) $p['costo_envio'] == 0 ? 'gratis' : money($p['costo_envio']));
    return 'Envío a ' . $dir . ' (' . $costo . ')';
}

/** Avisos por correo de un pedido nuevo: al local y, si dejó email, al cliente. */
function pedido_enviar_avisos(array $p, array $items): void
{
    $num = pedido_numero($p);
    $datos = ['p' => $p, 'items' => $items, 'numero' => $num];
    $aviso = correo_config()['avisos'];
    if (filter_var($aviso, FILTER_VALIDATE_EMAIL)) {
        correo_encolar('pedido_local', $aviso, "Nuevo pedido N° $num - " . $p['nombre'] . ' - ' . money($p['total']), 'pedido-local', $datos, $num);
    }
    if ($p['email']) {
        correo_encolar('pedido_cliente', $p['email'], "Recibimos tu pedido N° $num", 'pedido-cliente', $datos, $num);
    }
}
