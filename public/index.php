<?php
require __DIR__ . '/../app/bootstrap.php';

$banners = banners_activos();
$destacados = productos_destacados(10);

// Secciones por rubro en la home (slug del rubro => título)
$secciones = [];
foreach (['climatizacion' => 'Climatización', 'descanso' => 'Descanso', 'pequenos-electro' => 'Pequeños electrodomésticos'] as $slug => $titulo) {
    $cat = categoria_por_slug($slug);
    if (!$cat) {
        continue;
    }
    $items = productos_de_categorias(categoria_ids_con_hijos($cat), 5);
    if ($items) {
        $secciones[] = ['titulo' => $titulo, 'cat' => $cat, 'items' => $items];
    }
}

render('home', [
    'banners'    => $banners,
    'destacados' => $destacados,
    'accesos'    => categorias_menu(),
    'secciones'  => $secciones,
], ['clase' => 'pg-home']);
