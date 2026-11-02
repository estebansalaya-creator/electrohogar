<?php
/** Listado de productos con búsqueda, filtros y edición rápida de precio y stock. */
require __DIR__ . '/../../app/admin/bootstrap.php';
$yo = admin_requerido();
admin_verificar_post();

$volverA = admin_url('productos.php', array_filter([
    'q' => param('q'), 'cat' => param('cat'), 'estado' => param('estado'), 'pagina' => param('pagina'),
]));

if (es_post()) {
    $id = param_int('id');
    $p = q_one('SELECT id, nombre FROM productos WHERE id = ?', [$id]);
    if (!$p) {
        flash('error', 'Ese producto ya no existe.');
        redirect($volverA);
    }
    switch (param('accion')) {
        case 'rapido':
            $precio = parse_numero(param('precio'));
            $stock = param('stock');
            if ($precio === null || $precio <= 0) {
                flash('error', 'El precio de "' . $p['nombre'] . '" no es válido.');
            } elseif (!preg_match('/^\d+$/', $stock)) {
                flash('error', 'El stock de "' . $p['nombre'] . '" tiene que ser un número entero.');
            } else {
                q('UPDATE productos SET precio = ?, stock = ? WHERE id = ?', [$precio, (int) $stock, $id]);
                flash('ok', 'Actualizamos "' . $p['nombre'] . '".');
            }
            break;
        case 'alternar':
            $campo = param('campo');
            if (in_array($campo, ['activo', 'destacado'], true)) {
                q("UPDATE productos SET $campo = 1 - $campo WHERE id = ?", [$id]);
                flash('ok', 'Actualizamos "' . $p['nombre'] . '".');
            }
            break;
    }
    redirect($volverA);
}

$q = mb_substr(param('q'), 0, 80);
$cat = param_int('cat');
$estado = param('estado');
$where = ['1 = 1'];
$params = [];
if ($q !== '') {
    foreach (array_slice(preg_split('/\s+/u', $q) ?: [], 0, 5) as $t) {
        $where[] = "CONCAT_WS(' ', p.nombre, p.sku, m.nombre) LIKE ?";
        $params[] = '%' . addcslashes($t, '%_\\') . '%';
    }
}
if ($cat) {
    $where[] = '(p.categoria_id = ? OR c.parent_id = ?)';
    array_push($params, $cat, $cat);
}
$where[] = match ($estado) {
    'activos'    => 'p.activo = 1',
    'inactivos'  => 'p.activo = 0',
    'sin_stock'  => 'p.stock <= 0',
    'poco_stock' => 'p.stock BETWEEN 1 AND 3',
    'destacados' => 'p.destacado = 1',
    'ofertas'    => 'p.precio_lista > p.precio',
    'sin_foto'   => "(p.imagen IS NULL OR p.imagen = '' OR p.imagen LIKE 'productos/%.svg')",
    default      => '1 = 1',
};
$from = ' FROM productos p JOIN categorias c ON c.id = p.categoria_id LEFT JOIN marcas m ON m.id = p.marca_id WHERE ' . implode(' AND ', $where);
$total = (int) q_val('SELECT COUNT(*)' . $from, $params);
$porPagina = 30;
$paginas = max(1, (int) ceil($total / $porPagina));
$pagina = min(max(1, param_int('pagina', 1)), $paginas);
$items = q_all(
    'SELECT p.*, c.nombre AS categoria, m.nombre AS marca' . $from . ' ORDER BY p.activo DESC, p.nombre LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina),
    $params
);

render_admin('productos', [
    'items' => $items, 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas,
    'q' => $q, 'cat' => $cat, 'estado' => $estado, 'arbol' => categorias_arbol(), 'volverA' => $volverA,
], 'Productos', 'productos');
