<?php
/**
 * @var array|null $yo
 * @var string     $titulo
 * @var string     $seccion
 */
$menu = [
    ''                 => ['Inicio', 'index.php'],
    'pedidos'          => ['Pedidos', 'pedidos.php'],
    'productos'        => ['Productos', 'productos.php'],
    'categorias'       => ['Categorías', 'lista.php?e=categorias'],
    'marcas'           => ['Marcas', 'lista.php?e=marcas'],
    'banners'          => ['Banners', 'lista.php?e=banners'],
    'cupones'          => ['Cupones', 'lista.php?e=cupones'],
    'envios'           => ['Envíos', 'lista.php?e=envios'],
    'paginas'          => ['Páginas', 'lista.php?e=paginas'],
    'arrepentimientos' => ['Arrepentimientos', 'lista.php?e=arrepentimientos'],
    'newsletter'       => ['Suscriptores', 'lista.php?e=newsletter'],
    'importar'         => ['Importar productos', 'importar.php'],
    'correos'          => ['Correos', 'correos.php'],
    'mercadopago'      => ['Mercado Pago', 'mercadopago.php'],
    'ajustes'          => ['Datos de la tienda', 'ajustes.php'],
];
// Las credenciales de cobro solo las ve el administrador principal
if (!admin_es_principal($yo)) {
    unset($menu['mercadopago']);
}
// Cosas sin atender que se muestran como contador al lado de la sección
$menuPendientes = [];
if ($yo) {
    foreach ([
        'pedidos'          => ["SELECT COUNT(*) FROM pedidos WHERE estado = 'nuevo'", 'Pedidos nuevos'],
        'arrepentimientos' => ["SELECT COUNT(*) FROM arrepentimientos WHERE estado = 'recibida'", 'Sin atender'],
        'correos'          => ["SELECT COUNT(*) FROM correos WHERE estado = 'error' AND created_at > (NOW() - INTERVAL 7 DAY)", 'Con error'],
    ] as $clave => [$sql, $etiqueta]) {
        try {
            $cant = (int) q_val($sql);
            if ($cant) {
                $menuPendientes[$clave] = [$cant, $etiqueta];
            }
        } catch (Throwable $ex) {
            // la tabla puede no existir todavía si falta una migración
        }
    }
}
?><!doctype html>
<html lang="es-AR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($titulo) ?> | Panel <?= e(tienda('nombre')) ?></title>
<link rel="icon" href="<?= asset('img/marca/favicon-64.png') ?>" type="image/png">
<link rel="preload" href="<?= asset('fonts/archivo-latin-wdth-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
<script src="<?= asset('js/admin.js') ?>" defer></script>
</head>
<body class="<?= $yo ? 'con-menu' : 'sin-menu' ?>">
<?php if ($yo): ?>
<aside class="lateral" id="lateral" data-lateral>
  <a class="lateral__marca" href="<?= e(admin_url()) ?>">
    <img src="<?= asset('img/marca/logo-carlos-confort.png') ?>" alt="" width="348" height="152">
    <span>Panel</span>
  </a>
  <nav aria-label="Secciones del panel">
    <ul class="lateral__menu">
      <?php foreach ($menu as $clave => [$texto, $href]): ?>
        <li>
          <a href="<?= e(admin_url($href)) ?>"<?= $seccion === $clave ? ' aria-current="page"' : '' ?>>
            <?= e($texto) ?>
            <?php if (isset($menuPendientes[$clave])): ?><span class="contador" title="<?= e($menuPendientes[$clave][1]) ?>"><?= $menuPendientes[$clave][0] ?><span class="sr-only"> <?= e(mb_strtolower($menuPendientes[$clave][1])) ?></span></span><?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>
  <div class="lateral__pie">
    <a href="<?= e(url('')) ?>" target="_blank" rel="noopener">Ver la tienda</a>
    <a href="<?= e(admin_url('cuenta.php')) ?>"<?= $seccion === 'cuenta' ? ' aria-current="page"' : '' ?>>Mi cuenta</a>
    <form action="<?= e(admin_url('salir.php')) ?>" method="post">
      <?= csrf_field() ?>
      <button type="submit" class="link">Salir</button>
    </form>
  </div>
</aside>
<header class="barra">
  <button type="button" class="barra__menu" aria-controls="lateral" aria-expanded="false" data-lateral-toggle>Menú</button>
  <p class="barra__titulo"><?= e($titulo) ?></p>
  <p class="barra__usuario"><?= e($yo['nombre']) ?></p>
</header>
<?php endif; ?>
<main class="principal" id="contenido">
<?php foreach (flash_mensajes() as $f): ?>
  <p class="aviso aviso--<?= e($f['tipo']) ?>" role="status"><?= e($f['mensaje']) ?></p>
<?php endforeach; ?>
