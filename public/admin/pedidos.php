<?php
/** Listado de pedidos con filtro por estado, búsqueda y paginado. */
require __DIR__ . '/../../app/admin/bootstrap.php';
require_once APP_DIR . '/pedidos.php';
$yo = admin_requerido();

$q = mb_substr(param('q'), 0, 80);
$estado = param('estado');
if ($estado !== '' && $estado !== 'abiertos' && !isset(PEDIDO_ESTADOS[$estado])) {
    $estado = '';
}

$where = ['1 = 1'];
$params = [];
if ($q !== '') {
    $digitos = ltrim(preg_replace('/\D+/', '', $q) ?? '', '0');
    $partes = ["CONCAT_WS(' ', p.nombre, p.telefono, p.email, p.localidad) LIKE ?"];
    $params[] = '%' . addcslashes($q, '%_\\') . '%';
    if ($digitos !== '' && strlen($digitos) <= 9) {
        $partes[] = 'p.id = ?';
        $params[] = (int) $digitos;
    }
    $where[] = '(' . implode(' OR ', $partes) . ')';
}
if ($estado === 'abiertos') {
    $where[] = "p.estado IN ('nuevo', 'confirmado', 'preparando', 'enviado')";
} elseif ($estado !== '') {
    $where[] = 'p.estado = ?';
    $params[] = $estado;
}

$from = ' FROM pedidos p WHERE ' . implode(' AND ', $where);
$total = (int) q_val('SELECT COUNT(*)' . $from, $params);
$porPagina = 30;
$paginas = max(1, (int) ceil($total / $porPagina));
$pagina = min(max(1, param_int('pagina', 1)), $paginas);
$items = q_all(
    'SELECT p.*, (SELECT COALESCE(SUM(i.cantidad), 0) FROM pedido_items i WHERE i.pedido_id = p.id) AS unidades'
    . $from . ' ORDER BY p.id DESC LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina),
    $params
);
$conteo = array_column(q_all('SELECT estado, COUNT(*) AS n FROM pedidos GROUP BY estado'), 'n', 'estado');

render_admin('pedidos', compact('items', 'total', 'pagina', 'paginas', 'q', 'estado', 'conteo'), 'Pedidos', 'pedidos');
