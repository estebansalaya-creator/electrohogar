<?php
/**
 * Consultas a la base de datos.
 */
declare(strict_types=1);

const SQL_PRODUCTO = "
    SELECT p.*, m.nombre AS marca, m.slug AS marca_slug,
           c.nombre AS categoria, c.slug AS categoria_slug, c.parent_id AS categoria_parent_id
      FROM productos p
      JOIN categorias c ON c.id = p.categoria_id
 LEFT JOIN marcas m     ON m.id = p.marca_id";

// ---------------------------------------------------------------
// Categorías
// ---------------------------------------------------------------
function categorias_todas(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = q_all('SELECT * FROM categorias WHERE activo = 1 ORDER BY orden, nombre');
    }
    return $cache;
}

/** Rubros (nivel 1) con sus categorías hijas en 'hijos'. */
function categorias_arbol(): array
{
    $padres = [];
    $hijos = [];
    foreach (categorias_todas() as $c) {
        if ($c['parent_id'] === null) {
            $padres[$c['id']] = $c + ['hijos' => []];
        } else {
            $hijos[] = $c;
        }
    }
    foreach ($hijos as $h) {
        if (isset($padres[$h['parent_id']])) {
            $padres[$h['parent_id']]['hijos'][] = $h;
        }
    }
    return array_values($padres);
}

/** Accesos rápidos de la barra de navegación y de la home. */
function categorias_menu(): array
{
    return array_values(array_filter(categorias_todas(), fn($c) => (int) $c['en_menu'] === 1));
}

function categoria_por_slug(string $slug): ?array
{
    foreach (categorias_todas() as $c) {
        if ($c['slug'] === $slug) {
            return $c;
        }
    }
    return null;
}

function categoria_por_id(?int $id): ?array
{
    foreach (categorias_todas() as $c) {
        if ((int) $c['id'] === $id) {
            return $c;
        }
    }
    return null;
}

/** IDs de la categoría y de sus hijas. */
function categoria_ids_con_hijos(array $cat): array
{
    $ids = [(int) $cat['id']];
    foreach (categorias_todas() as $c) {
        if ((int) $c['parent_id'] === (int) $cat['id']) {
            $ids[] = (int) $c['id'];
        }
    }
    return $ids;
}

function categorias_hijas(array $cat): array
{
    return array_values(array_filter(categorias_todas(), fn($c) => (int) $c['parent_id'] === (int) $cat['id']));
}

// ---------------------------------------------------------------
// Productos
// ---------------------------------------------------------------
function productos_destacados(int $limite = 10): array
{
    $limite = max(1, min(50, $limite));
    return q_all(SQL_PRODUCTO . " WHERE p.activo = 1 AND p.destacado = 1 ORDER BY p.vistas DESC, p.id LIMIT $limite");
}

function productos_de_categorias(array $ids, int $limite = 5): array
{
    if (!$ids) {
        return [];
    }
    $limite = max(1, min(50, $limite));
    $in = implode(',', array_fill(0, count($ids), '?'));
    return q_all(
        SQL_PRODUCTO . " WHERE p.activo = 1 AND p.categoria_id IN ($in) ORDER BY p.stock > 0 DESC, p.vistas DESC LIMIT $limite",
        array_values($ids)
    );
}

function producto_por_slug(string $slug): ?array
{
    return q_one(SQL_PRODUCTO . ' WHERE p.slug = ? AND p.activo = 1', [$slug]);
}

/** Productos activos por id, indexados por id. */
function productos_por_ids(array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) {
        return [];
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $out = [];
    foreach (q_all(SQL_PRODUCTO . " WHERE p.activo = 1 AND p.id IN ($in)", $ids) as $p) {
        $out[(int) $p['id']] = $p;
    }
    return $out;
}

function producto_imagenes(array $p): array
{
    $rutas = array_column(q_all('SELECT ruta FROM producto_imagenes WHERE producto_id = ? ORDER BY orden, id', [$p['id']]), 'ruta');
    if ($p['imagen'] && !in_array($p['imagen'], $rutas, true)) {
        array_unshift($rutas, $p['imagen']);
    }
    return $rutas ?: [null];
}

function producto_specs(int $id): array
{
    return q_all('SELECT nombre, valor FROM producto_specs WHERE producto_id = ? ORDER BY orden, id', [$id]);
}

function productos_relacionados(array $p, int $limite = 5): array
{
    $limite = max(1, min(20, $limite));
    return q_all(
        SQL_PRODUCTO . " WHERE p.activo = 1 AND p.categoria_id = ? AND p.id <> ? ORDER BY p.stock > 0 DESC, p.vistas DESC LIMIT $limite",
        [$p['categoria_id'], $p['id']]
    );
}

function producto_sumar_vista(int $id): void
{
    q('UPDATE productos SET vistas = vistas + 1 WHERE id = ?', [$id]);
}

/**
 * Búsqueda con filtros para los listados.
 * $f: cat_ids[], q, ofertas, marca, min, max, envio, orden, pagina, por_pagina
 * Devuelve: items, total, marcas (con cantidad), paginas
 */
function buscar_productos(array $f): array
{
    $where = ['p.activo = 1'];
    $params = [];

    if (!empty($f['cat_ids'])) {
        $where[] = 'p.categoria_id IN (' . implode(',', array_fill(0, count($f['cat_ids']), '?')) . ')';
        array_push($params, ...array_map('intval', $f['cat_ids']));
    }
    if (!empty($f['q'])) {
        $terminos = array_slice(preg_split('/\s+/u', mb_substr($f['q'], 0, 80)) ?: [], 0, 6);
        foreach ($terminos as $t) {
            if ($t === '') {
                continue;
            }
            $where[] = "CONCAT_WS(' ', p.nombre, m.nombre, p.sku, c.nombre) LIKE ?";
            $params[] = '%' . addcslashes($t, '%_\\') . '%';
        }
    }
    if (!empty($f['ofertas'])) {
        $where[] = 'p.precio_lista > p.precio';
    }
    if (!empty($f['envio'])) {
        $where[] = 'p.envio_gratis = 1';
    }
    if (!empty($f['min'])) {
        $where[] = 'p.precio >= ?';
        $params[] = (float) $f['min'];
    }
    if (!empty($f['max'])) {
        $where[] = 'p.precio <= ?';
        $params[] = (float) $f['max'];
    }

    // Facetas de marca: se calculan con todos los filtros menos el de marca.
    $baseFrom = ' FROM productos p JOIN categorias c ON c.id = p.categoria_id LEFT JOIN marcas m ON m.id = p.marca_id WHERE ';
    $marcas = q_all(
        'SELECT m.nombre, m.slug, COUNT(*) AS cantidad' . $baseFrom . implode(' AND ', $where) .
        ' AND m.id IS NOT NULL GROUP BY m.id, m.nombre, m.slug ORDER BY m.nombre',
        $params
    );

    if (!empty($f['marca'])) {
        $where[] = 'm.slug = ?';
        $params[] = $f['marca'];
    }

    $sqlWhere = implode(' AND ', $where);
    $total = (int) q_val('SELECT COUNT(*)' . $baseFrom . $sqlWhere, $params);

    $ordenes = [
        'relevancia' => 'p.stock > 0 DESC, p.destacado DESC, p.vistas DESC, p.id',
        'menor'      => 'p.precio ASC, p.id',
        'mayor'      => 'p.precio DESC, p.id',
        'descuento'  => '(CASE WHEN p.precio_lista > p.precio THEN 1 - p.precio / p.precio_lista ELSE 0 END) DESC, p.id',
        'nombre'     => 'p.nombre ASC, p.id',
    ];
    $orden = $ordenes[$f['orden'] ?? ''] ?? $ordenes['relevancia'];

    $porPagina = max(1, (int) ($f['por_pagina'] ?? 12));
    $paginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min(max(1, (int) ($f['pagina'] ?? 1)), $paginas);
    $offset = ($pagina - 1) * $porPagina;

    $items = q_all(SQL_PRODUCTO . " WHERE $sqlWhere ORDER BY $orden LIMIT $porPagina OFFSET $offset", $params);

    return compact('items', 'total', 'marcas', 'pagina', 'paginas');
}

// ---------------------------------------------------------------
// Contenido
// ---------------------------------------------------------------
function banners_activos(): array
{
    return q_all(
        'SELECT * FROM banners
          WHERE activo = 1
            AND (desde IS NULL OR desde <= CURDATE())
            AND (hasta IS NULL OR hasta >= CURDATE())
          ORDER BY orden, id'
    );
}

function pagina_por_slug(string $slug): ?array
{
    return q_one('SELECT * FROM paginas WHERE slug = ?', [$slug]);
}

// ---------------------------------------------------------------
// Cupones y envíos
// ---------------------------------------------------------------
function cupon_por_codigo(string $codigo): ?array
{
    return q_one(
        'SELECT * FROM cupones
          WHERE codigo = ? AND activo = 1
            AND (desde IS NULL OR desde <= CURDATE())
            AND (hasta IS NULL OR hasta >= CURDATE())',
        [mb_strtoupper(trim($codigo))]
    );
}

function cp_normalizar(string $cp): ?int
{
    // Acepta "1425" o CPA "C1425ABC": tomamos los 4 dígitos.
    if (preg_match('/^[A-Za-z]?(\d{4})[A-Za-z]{0,3}$/', trim($cp), $m)) {
        return (int) $m[1];
    }
    return null;
}

function zona_por_cp(int $cp): ?array
{
    return q_one(
        'SELECT * FROM zonas_envio WHERE ? BETWEEN cp_desde AND cp_hasta ORDER BY prioridad DESC, id LIMIT 1',
        [$cp]
    );
}

/**
 * Cotiza el envío de un conjunto de productos a un CP.
 * Gratis si la zona lo permite y TODOS los productos tienen envío gratis.
 */
function cotizar_envio(int $cp, array $productos): array
{
    $zona = zona_por_cp($cp);
    if (!$zona) {
        return ['ok' => false, 'mensaje' => 'No tenemos tarifa para ese código postal. Escribinos y lo cotizamos.'];
    }
    $todosGratis = $productos && array_reduce($productos, fn($acc, $p) => $acc && (int) $p['envio_gratis'] === 1, true);
    $gratis = (int) $zona['permite_gratis'] === 1 && $todosGratis;
    $costo = $gratis ? 0.0 : (float) $zona['costo'];
    return [
        'ok'     => true,
        'cp'     => $cp,
        'zona'   => $zona['nombre'],
        'gratis' => $gratis,
        'costo'  => $costo,
        'dias'   => $zona['dias'],
    ];
}

// ---------------------------------------------------------------
// Newsletter y arrepentimiento
// ---------------------------------------------------------------
/** Devuelve true si es una suscripción nueva. */
function newsletter_suscribir(string $email): bool
{
    $st = q('INSERT IGNORE INTO newsletter (email, ip) VALUES (?, ?)', [mb_strtolower($email), ip_cliente()]);
    return $st->rowCount() > 0;
}

/** Guarda la solicitud y devuelve el código de trámite. */
function arrepentimiento_crear(array $d): string
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        q(
            'INSERT INTO arrepentimientos (nombre, email, telefono, dni, nro_pedido, fecha_compra, producto, motivo, ip)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $d['nombre'], $d['email'], $d['telefono'] ?: null, $d['dni'], $d['nro_pedido'],
                $d['fecha_compra'] ?: null, $d['producto'] ?: null, $d['motivo'] ?: null, ip_cliente(),
            ]
        );
        $id = (int) $pdo->lastInsertId();
        $codigo = sprintf('ARR-%s-%06d', date('Y'), $id);
        q('UPDATE arrepentimientos SET codigo = ? WHERE id = ?', [$codigo, $id]);
        $pdo->commit();
        return $codigo;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}
