<?php
/**
 * Carrito en la sesión. Los precios se leen SIEMPRE de la base:
 * en la sesión solo se guardan id de producto y cantidad.
 *
 * $_SESSION['carrito'] = ['items' => [id => cantidad], 'cupon' => 'CODIGO'|null, 'cp' => 1425|null]
 */
declare(strict_types=1);

function &carrito_sesion(): array
{
    if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }
    $_SESSION['carrito'] += ['items' => [], 'cupon' => null, 'cp' => null];
    return $_SESSION['carrito'];
}

function carrito_max_unidades(array $p): int
{
    return max(0, min((int) $p['stock'], (int) config('ventas.max_unidades', 10)));
}

/** @return array{ok: bool, mensaje: string, producto?: array} */
function carrito_agregar(int $id, int $cantidad = 1): array
{
    $p = productos_por_ids([$id])[$id] ?? null;
    if (!$p) {
        return ['ok' => false, 'mensaje' => 'Ese producto ya no está disponible.'];
    }
    $max = carrito_max_unidades($p);
    if ($max === 0) {
        return ['ok' => false, 'mensaje' => 'Ese producto no tiene stock en este momento.'];
    }
    $c = &carrito_sesion();
    $actual = (int) ($c['items'][$id] ?? 0);
    $nueva = min($max, $actual + max(1, $cantidad));
    $c['items'][$id] = $nueva;

    if ($nueva < $actual + max(1, $cantidad)) {
        return ['ok' => true, 'producto' => $p, 'mensaje' => "Podés llevar hasta $max " . plural($max, 'unidad', 'unidades') . ' de este producto. Ya están en tu carrito.'];
    }
    return ['ok' => true, 'producto' => $p, 'mensaje' => 'Agregaste ' . $p['nombre'] . ' al carrito.'];
}

function carrito_actualizar(int $id, int $cantidad): array
{
    $c = &carrito_sesion();
    if (!isset($c['items'][$id])) {
        return ['ok' => false, 'mensaje' => 'Ese producto no está en tu carrito.'];
    }
    if ($cantidad <= 0) {
        return carrito_quitar($id);
    }
    $p = productos_por_ids([$id])[$id] ?? null;
    $max = $p ? carrito_max_unidades($p) : 0;
    if ($max === 0) {
        unset($c['items'][$id]);
        return ['ok' => false, 'mensaje' => 'Sacamos un producto que se quedó sin stock.'];
    }
    $c['items'][$id] = min($cantidad, $max);
    if ($cantidad > $max) {
        return ['ok' => true, 'mensaje' => "Hay $max " . plural($max, 'unidad disponible', 'unidades disponibles') . ' de este producto.'];
    }
    return ['ok' => true, 'mensaje' => 'Actualizamos la cantidad.'];
}

function carrito_quitar(int $id): array
{
    $c = &carrito_sesion();
    unset($c['items'][$id]);
    return ['ok' => true, 'mensaje' => 'Sacamos el producto del carrito.'];
}

function carrito_vaciar(): void
{
    $_SESSION['carrito'] = ['items' => [], 'cupon' => null, 'cp' => null];
}

function carrito_cantidad(): int
{
    return array_sum(array_map('intval', $_SESSION['carrito']['items'] ?? []));
}

function carrito_aplicar_cupon(string $codigo): array
{
    $codigo = mb_strtoupper(trim($codigo));
    if ($codigo === '') {
        return ['ok' => false, 'mensaje' => 'Escribí el código del cupón.'];
    }
    $cupon = cupon_por_codigo($codigo);
    if (!$cupon) {
        return ['ok' => false, 'mensaje' => "El cupón $codigo no existe o está vencido."];
    }
    $subtotal = carrito_resumen()['subtotal'];
    if ($subtotal < (float) $cupon['minimo_compra']) {
        return ['ok' => false, 'mensaje' => 'El cupón ' . $codigo . ' es para compras desde ' . money($cupon['minimo_compra']) . '.'];
    }
    $c = &carrito_sesion();
    $c['cupon'] = $cupon['codigo'];
    return ['ok' => true, 'mensaje' => "Aplicamos el cupón $codigo."];
}

function carrito_quitar_cupon(): array
{
    $c = &carrito_sesion();
    $c['cupon'] = null;
    return ['ok' => true, 'mensaje' => 'Quitamos el cupón.'];
}

function carrito_set_cp(string $cp): array
{
    $n = cp_normalizar($cp);
    if ($n === null) {
        return ['ok' => false, 'mensaje' => 'Escribí un código postal de 4 números, por ejemplo 1425.'];
    }
    $c = &carrito_sesion();
    $c['cp'] = $n;
    return ['ok' => true, 'mensaje' => 'Calculamos el envío para el código postal ' . $n . '.'];
}

/**
 * Calcula todo el carrito a partir de la base.
 */
function carrito_resumen(): array
{
    $c = &carrito_sesion();
    $productos = productos_por_ids(array_keys($c['items']));
    $lineas = [];
    $subtotal = 0.0;
    $sinImpuestos = 0.0;
    $cuotas = null;
    $avisos = [];

    foreach ($c['items'] as $id => $cant) {
        $p = $productos[(int) $id] ?? null;
        $max = $p ? carrito_max_unidades($p) : 0;
        if (!$p || $max === 0) {
            unset($c['items'][$id]);
            $avisos[] = 'Sacamos del carrito un producto que ya no está disponible.';
            continue;
        }
        if ($cant > $max) {
            $c['items'][$id] = $cant = $max;
            $avisos[] = 'Ajustamos la cantidad de ' . $p['nombre'] . ' al stock disponible.';
        }
        $sub = (float) $p['precio'] * $cant;
        $subtotal += $sub;
        $sinImpuestos += $sub / (1 + (float) $p['iva'] / 100);
        $n = (int) $p['cuotas_sin_interes'];
        $cuotas = $cuotas === null ? $n : min($cuotas, $n);
        $lineas[] = ['producto' => $p, 'cantidad' => (int) $cant, 'max' => $max, 'subtotal' => $sub];
    }

    // Cupón
    $cupon = null;
    $descuento = 0.0;
    if (!empty($c['cupon'])) {
        $cupon = cupon_por_codigo($c['cupon']);
        if (!$cupon || $subtotal < (float) $cupon['minimo_compra']) {
            $avisos[] = 'El cupón ' . $c['cupon'] . ' ya no aplica a este carrito y lo quitamos.';
            $c['cupon'] = null;
            $cupon = null;
        } else {
            $descuento = $cupon['tipo'] === 'porcentaje'
                ? round($subtotal * (float) $cupon['valor'] / 100, 2)
                : min($subtotal, (float) $cupon['valor']);
        }
    }
    $productosNeto = $subtotal - $descuento;
    if ($subtotal > 0) {
        $sinImpuestos *= $productosNeto / $subtotal;
    }

    // Envío
    $envio = null;
    if (!empty($c['cp']) && $lineas) {
        $envio = cotizar_envio((int) $c['cp'], array_column($lineas, 'producto'));
    }
    $costoEnvio = ($envio && $envio['ok']) ? (float) $envio['costo'] : 0.0;

    $total = $productosNeto + $costoEnvio;
    $totalTransferencia = precio_transferencia($productosNeto) + $costoEnvio;
    $cuotas = (int) ($cuotas ?? 0);

    return [
        'lineas'              => $lineas,
        'cantidad'            => array_sum(array_column($lineas, 'cantidad')),
        'subtotal'            => $subtotal,
        'cupon'               => $cupon,
        'descuento'           => $descuento,
        'cp'                  => $c['cp'] ?? null,
        'envio'               => $envio,
        'total'               => $total,
        'total_transferencia' => $totalTransferencia,
        'sin_impuestos'       => round($sinImpuestos, 2),
        'cuotas'              => $cuotas,
        'valor_cuota'         => $cuotas > 0 ? $total / $cuotas : 0,
        'avisos'              => array_values(array_unique($avisos)),
    ];
}

/** Versión reducida para el mini carrito (JSON). */
function carrito_json(?array $r = null): array
{
    $r = $r ?? carrito_resumen();
    return [
        'cantidad' => $r['cantidad'],
        'items'    => array_map(fn($l) => [
            'id'       => (int) $l['producto']['id'],
            'nombre'   => $l['producto']['nombre'],
            'marca'    => $l['producto']['marca'],
            'url'      => url_producto($l['producto']),
            'imagen'   => img($l['producto']['imagen']),
            'cantidad' => $l['cantidad'],
            'precio'   => money($l['producto']['precio']),
            'subtotal' => money($l['subtotal']),
        ], $r['lineas']),
        'subtotal'            => money($r['subtotal']),
        'total'               => money($r['total']),
        'total_transferencia' => money($r['total_transferencia']),
    ];
}

/** Texto del pedido para enviar por WhatsApp. */
function carrito_texto_whatsapp(array $r): string
{
    $t = 'Hola ' . tienda('nombre') . ", quiero hacer este pedido:\n\n";
    foreach ($r['lineas'] as $l) {
        $p = $l['producto'];
        $t .= '• ' . $l['cantidad'] . ' x ' . $p['nombre'] . ($p['marca'] ? ' (' . $p['marca'] . ')' : '')
            . ' [' . $p['sku'] . '] - ' . money($l['subtotal']) . "\n";
    }
    $t .= "\nSubtotal: " . money($r['subtotal']);
    if ($r['cupon']) {
        $t .= "\nCupón " . $r['cupon']['codigo'] . ': -' . money($r['descuento']);
    }
    if ($r['envio'] && $r['envio']['ok']) {
        $t .= "\nEnvío a CP " . $r['envio']['cp'] . ' (' . $r['envio']['zona'] . '): '
            . ($r['envio']['gratis'] ? 'gratis' : money($r['envio']['costo']));
    }
    $t .= "\nTotal: " . money($r['total']);
    $t .= "\nTotal con transferencia: " . money($r['total_transferencia']);
    $t .= "\n\n¿Me confirman stock y forma de entrega?";
    return $t;
}
