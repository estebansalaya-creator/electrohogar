<?php
/** @var array $meta */
$nombreTienda = tienda('nombre');
$tituloDoc = $meta['titulo'] ? $meta['titulo'] . ' | ' . $nombreTienda : $nombreTienda . ' | Electrodomésticos y hogar en cuotas';
$descDoc = $meta['descripcion'] ?: 'Carlos Confort, la mejor elección para su hogar. Electrodomésticos, climatización, TV y colchones en hasta 12 cuotas sin interés.';
$arbol = categorias_arbol();
$menuRapido = categorias_menu();
$cantCarrito = carrito_cantidad();
$textoBusqueda = param('q');
$telLink = 'tel:' . preg_replace('/[^\d+]/', '', tienda('telefono'));
?><!doctype html>
<html lang="es-AR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($tituloDoc) ?></title>
<meta name="description" content="<?= e($descDoc) ?>">
<?php if ($meta['noindex']): ?><meta name="robots" content="noindex"><?php endif; ?>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="api-carrito" content="<?= e(url('api/carrito.php')) ?>">
<meta name="api-base" content="<?= e(url('api/')) ?>">
<meta name="theme-color" content="#0A4B5C">
<link rel="icon" href="<?= asset('img/marca/favicon-64.png') ?>" type="image/png" sizes="64x64">
<link rel="apple-touch-icon" href="<?= asset('img/marca/apple-touch-icon.png') ?>">
<link rel="preload" href="<?= asset('fonts/archivo-latin-wdth-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="<?= asset('js/app.js') ?>" defer></script>
</head>
<body class="<?= e($meta['clase']) ?>">
<a class="skip" href="#contenido">Saltar al contenido</a>

<div class="topbar">
  <div class="wrap topbar__in">
    <p class="topbar__promo"><?= icono('camion') ?> Envío gratis en seleccionados a CABA y GBA. Llegamos a todo el país.</p>
    <ul class="topbar__links">
      <li><a href="<?= e($telLink) ?>"><?= icono('telefono') ?><span><?= e(tienda('telefono')) ?></span></a></li>
      <li><a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener"><?= icono('whatsapp') ?><span>WhatsApp</span></a></li>
      <li><a href="<?= e(tienda('instagram')) ?>" target="_blank" rel="noopener"><?= icono('instagram') ?><span>Instagram</span></a></li>
    </ul>
  </div>
</div>

<header class="header">
  <div class="wrap header__in">
    <button class="header__burger" type="button" aria-controls="menu-categorias" aria-expanded="false" data-menu-toggle>
      <?= icono('menu') ?><span class="sr-only">Ver categorías</span>
    </button>

    <a class="logo" href="<?= e(url('')) ?>">
      <picture>
        <source srcset="<?= asset('img/marca/logo-carlos-confort.webp') ?>" type="image/webp">
        <img src="<?= asset('img/marca/logo-carlos-confort.png') ?>" alt="<?= e($nombreTienda) ?>, ir al inicio" width="348" height="152">
      </picture>
    </a>

    <form class="search" action="<?= e(url('productos.php')) ?>" method="get" role="search">
      <label class="sr-only" for="buscador">Buscar productos</label>
      <input id="buscador" name="q" type="search" value="<?= e($textoBusqueda) ?>" placeholder="Buscá heladeras, aires, colchones…" autocomplete="off" maxlength="80">
      <button type="submit"><?= icono('lupa') ?><span class="sr-only">Buscar</span></button>
    </form>

    <a class="header__action" href="<?= e(url('pagina.php', ['slug' => 'preguntas-frecuentes'])) ?>">
      <?= icono('ayuda') ?><span>Ayuda</span>
    </a>
    <a class="header__action header__cart" href="<?= e(url('carrito.php')) ?>" data-cart-open>
      <?= icono('carrito') ?><span>Carrito</span>
      <span class="badge" data-cart-count<?= $cantCarrito ? '' : ' hidden' ?>><?= $cantCarrito ?></span>
    </a>
  </div>
</header>

<nav class="nav" aria-label="Categorías">
  <div class="wrap nav__in">
    <button class="nav__all" type="button" aria-controls="menu-categorias" aria-expanded="false" data-menu-toggle>
      <?= icono('menu') ?> Categorías <?= icono('abajo', 'nav__chev') ?>
    </button>
    <ul class="nav__quick">
      <?php foreach ($menuRapido as $c): ?>
        <li><a href="<?= e(url_categoria($c['slug'])) ?>"><?= e($c['nombre']) ?></a></li>
      <?php endforeach; ?>
      <li><a class="nav__ofertas" href="<?= e(url('productos.php', ['ofertas' => 1])) ?>">Ofertas</a></li>
    </ul>
  </div>

  <div class="mega" id="menu-categorias" hidden data-menu>
    <div class="wrap mega__in">
      <div class="mega__head">
        <p class="mega__title">Categorías</p>
        <button type="button" class="icon-btn" data-menu-close><?= icono('cerrar') ?><span class="sr-only">Cerrar</span></button>
      </div>
      <div class="mega__grid">
        <?php foreach ($arbol as $rubro): ?>
          <div class="mega__col">
            <a class="mega__rubro" href="<?= e(url_categoria($rubro['slug'])) ?>"><?= e($rubro['nombre']) ?></a>
            <ul>
              <?php foreach ($rubro['hijos'] as $h): ?>
                <li><a href="<?= e(url_categoria($h['slug'])) ?>"><?= e($h['nombre']) ?></a></li>
              <?php endforeach; ?>
            </ul>
            <a class="mega__todo" href="<?= e(url_categoria($rubro['slug'])) ?>">Ver todo en <?= e($rubro['nombre']) ?></a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</nav>

<main id="contenido" tabindex="-1">
<?php $flashes = flash_mensajes(); if ($flashes): ?>
  <div class="wrap flashes">
    <?php foreach ($flashes as $f): ?>
      <p class="flash flash--<?= e($f['tipo']) ?>" role="status"><?= e($f['mensaje']) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
