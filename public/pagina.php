<?php
/** Páginas institucionales: pagina.php?slug=como-comprar */
require __DIR__ . '/../app/bootstrap.php';

$slug = param('slug');
$pagina = es_slug($slug) ? pagina_por_slug($slug) : null;
if (!$pagina) {
    no_encontrado();
}

render('pagina', ['pagina' => $pagina], ['titulo' => $pagina['titulo'], 'clase' => 'pg-pagina']);
