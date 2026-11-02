<?php
/**
 * Listado de productos: por categoría (?cat=), búsqueda (?q=) u ofertas (?ofertas=1).
 * Filtros: marca, min, max, envio, orden, pagina.
 */
require __DIR__ . '/../app/bootstrap.php';

$catSlug = param('cat');
$q = mb_substr(param('q'), 0, 80);
$ofertas = param('ofertas') === '1';
$marca = param('marca');
$min = max(0, param_int('min'));
$max = max(0, param_int('max'));
$envio = param('envio') === '1';
$orden = param('orden', 'relevancia');
$pagina = max(1, param_int('pagina', 1));

$categoria = null;
$catIds = [];
if ($catSlug !== '') {
    $categoria = es_slug($catSlug) ? categoria_por_slug($catSlug) : null;
    if (!$categoria) {
        no_encontrado('Esta categoría no existe o ya no está disponible.');
    }
    $catIds = categoria_ids_con_hijos($categoria);
}
if ($marca !== '' && !es_slug($marca)) {
    $marca = '';
}
if (!in_array($orden, ['relevancia', 'menor', 'mayor', 'descuento', 'nombre'], true)) {
    $orden = 'relevancia';
}
if ($min && $max && $min > $max) {
    [$min, $max] = [$max, $min];
}

$resultado = buscar_productos([
    'cat_ids'    => $catIds,
    'q'          => $q,
    'ofertas'    => $ofertas,
    'marca'      => $marca,
    'min'        => $min,
    'max'        => $max,
    'envio'      => $envio,
    'orden'      => $orden,
    'pagina'     => $pagina,
    'por_pagina' => (int) config('ventas.por_pagina', 12),
]);

// Título y migas
$padre = $categoria && $categoria['parent_id'] ? categoria_por_id((int) $categoria['parent_id']) : null;
$migas = [];
if ($padre) {
    $migas[] = [$padre['nombre'], url_categoria($padre['slug'])];
}
if ($categoria) {
    $titulo = $categoria['nombre'];
    $migas[] = [$categoria['nombre'], null];
} elseif ($q !== '') {
    $titulo = 'Resultados para “' . $q . '”';
    $migas[] = ['Búsqueda', null];
} elseif ($ofertas) {
    $titulo = 'Ofertas';
    $migas[] = ['Ofertas', null];
} else {
    $titulo = 'Todos los productos';
    $migas[] = ['Productos', null];
}

// Parámetros actuales (para armar links de filtros y paginación)
$query = [
    'cat'     => $categoria['slug'] ?? null,
    'q'       => $q ?: null,
    'ofertas' => $ofertas ? 1 : null,
    'marca'   => $marca ?: null,
    'min'     => $min ?: null,
    'max'     => $max ?: null,
    'envio'   => $envio ? 1 : null,
    'orden'   => $orden !== 'relevancia' ? $orden : null,
];

// Subcategorías para el filtro lateral
$subcats = [];
if ($categoria) {
    $subcats = categorias_hijas($padre ?? $categoria);
}

render('listado', [
    'titulo'    => $titulo,
    'migas'     => $migas,
    'categoria' => $categoria,
    'padre'     => $padre,
    'subcats'   => $subcats,
    'resultado' => $resultado,
    'query'     => $query,
    'filtros'   => compact('q', 'ofertas', 'marca', 'min', 'max', 'envio', 'orden'),
], [
    'titulo'      => $titulo,
    'descripcion' => $categoria ? $categoria['nombre'] . ' en hasta 12 cuotas sin interés y con envío a todo el país.' : null,
    'noindex'     => $q !== '',
    'clase'       => 'pg-listado',
]);
