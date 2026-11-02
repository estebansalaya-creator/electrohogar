<?php
require __DIR__ . '/../app/bootstrap.php';

$slug = param('slug');
$p = es_slug($slug) ? producto_por_slug($slug) : null;
if (!$p) {
    no_encontrado('Este producto no existe o ya no está publicado.');
}

// Una vista por sesión y producto
if (empty($_SESSION['vistos'][$p['id']])) {
    $_SESSION['vistos'][$p['id']] = 1;
    producto_sumar_vista((int) $p['id']);
}

$categoria = categoria_por_id((int) $p['categoria_id']);
$padre = $categoria && $categoria['parent_id'] ? categoria_por_id((int) $categoria['parent_id']) : null;
$migas = [];
if ($padre) {
    $migas[] = [$padre['nombre'], url_categoria($padre['slug'])];
}
if ($categoria) {
    $migas[] = [$categoria['nombre'], url_categoria($categoria['slug'])];
}
$migas[] = [$p['nombre'], null];

render('producto', [
    'p'            => $p,
    'migas'        => $migas,
    'imagenes'     => producto_imagenes($p),
    'specs'        => producto_specs((int) $p['id']),
    'relacionados' => productos_relacionados($p, 5),
    'cpGuardado'   => $_SESSION['carrito']['cp'] ?? null,
], [
    'titulo'      => $p['nombre'] . ($p['marca'] ? ' ' . $p['marca'] : ''),
    'descripcion' => mb_substr(strip_tags((string) $p['descripcion']), 0, 155),
    'clase'       => 'pg-producto',
]);
