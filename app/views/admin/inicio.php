<?php
/**
 * @var array $yo
 * @var array $resumen
 * @var array $pocoStock
 * @var array $arrepentimientos
 * @var array $pendientes
 * @var array $ultimosPedidos
 */
$cifras = [
    ['Pedidos nuevos', $resumen['pedidos_nuevos'], admin_url('pedidos.php', ['estado' => 'nuevo']), $resumen['pedidos_nuevos'] ? 'urgente' : ''],
    ['Pedidos en curso', $resumen['pedidos_curso'], admin_url('pedidos.php', ['estado' => 'abiertos']), ''],
    ['Productos publicados', $resumen['publicados'], admin_url('productos.php', ['estado' => 'activos']), ''],
    ['Sin stock', $resumen['sin_stock'], admin_url('productos.php', ['estado' => 'sin_stock']), $resumen['sin_stock'] ? 'alerta' : ''],
    ['Sin foto real', $resumen['sin_foto'], admin_url('productos.php', ['estado' => 'sin_foto']), $resumen['sin_foto'] ? 'alerta' : ''],
    ['Arrepentimientos sin atender', $resumen['arrep'], admin_url('lista.php', ['e' => 'arrepentimientos', 'filtro' => 'recibida']), $resumen['arrep'] ? 'urgente' : ''],
    ['Suscriptores', $resumen['suscriptores'], admin_url('lista.php', ['e' => 'newsletter']), ''],
];
?>
<div class="encabezado">
  <h1>Hola, <?= e(explode(' ', $yo['nombre'])[0]) ?></h1>
  <div class="encabezado__acciones">
    <a class="btn btn--primario" href="<?= e(admin_url('producto.php')) ?>">Nuevo producto</a>
  </div>
</div>

<?php if ($pendientes): ?>
  <section class="tarjeta pendientes" aria-labelledby="t-pend">
    <h2 id="t-pend">Antes de publicar la tienda</h2>
    <ul>
      <?php foreach ($pendientes as $txt): ?><li><?= e($txt) ?></li><?php endforeach; ?>
    </ul>
    <a class="btn btn--sec btn--chico" href="<?= e(admin_url('ajustes.php')) ?>">Completar datos de la tienda</a>
  </section>
<?php endif; ?>

<ul class="cifras">
  <?php foreach ($cifras as [$txt, $valor, $href, $tono]): ?>
    <li><a class="cifra<?= $tono ? ' cifra--' . $tono : '' ?>" href="<?= e($href) ?>"><strong><?= $valor ?></strong><span><?= e($txt) ?></span></a></li>
  <?php endforeach; ?>
</ul>

<section class="tarjeta" aria-labelledby="t-ped">
  <h2 id="t-ped">Últimos pedidos</h2>
  <?php if (!$ultimosPedidos): ?>
    <p class="tenue">Todavía no entró ningún pedido desde la tienda.</p>
  <?php else: ?>
    <ul class="lista-simple">
      <?php foreach ($ultimosPedidos as $p): ?>
        <li>
          <a href="<?= e(admin_url('pedido.php', ['id' => $p['id']])) ?>">N° <?= e(pedido_numero($p)) ?> · <?= e($p['nombre']) ?></a>
          <span class="tenue col-fecha"><?= e(date('d/m H:i', strtotime($p['created_at']))) ?></span>
          <span class="num"><?= money($p['total']) ?></span>
          <span class="estado estado--<?= e($p['estado']) ?>"><?= e(pedido_estado_nombre($p)) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <a class="link mt" href="<?= e(admin_url('pedidos.php')) ?>">Ver todos los pedidos</a>
  <?php endif; ?>
</section>

<div class="columnas">
  <section class="tarjeta" aria-labelledby="t-stock">
    <h2 id="t-stock">Stock bajo</h2>
    <?php if (!$pocoStock): ?>
      <p class="tenue">Todos los productos publicados tienen más de 3 unidades.</p>
    <?php else: ?>
      <ul class="lista-simple">
        <?php foreach ($pocoStock as $p): ?>
          <li>
            <img class="miniatura" src="<?= e(img($p['imagen'])) ?>" alt="" width="36" height="36" loading="lazy">
            <a href="<?= e(admin_url('producto.php', ['id' => $p['id']])) ?>"><?= e($p['nombre']) ?></a>
            <span class="<?= $p['stock'] <= 0 ? 'stock-cero' : 'stock-poco' ?>"><?= $p['stock'] <= 0 ? 'Sin stock' : (int) $p['stock'] . ' u.' ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="tarjeta" aria-labelledby="t-arr">
    <h2 id="t-arr">Arrepentimientos sin atender</h2>
    <?php if (!$arrepentimientos): ?>
      <p class="tenue">No hay solicitudes nuevas.</p>
    <?php else: ?>
      <p class="campo__ayuda">Hay que responder al cliente: tiene derecho a devolver el producto dentro de los 10 días.</p>
      <ul class="lista-simple">
        <?php foreach ($arrepentimientos as $a): ?>
          <li>
            <a href="<?= e(admin_url('editar.php', ['e' => 'arrepentimientos', 'id' => $a['id']])) ?>"><?= e($a['codigo']) ?> · <?= e($a['nombre']) ?></a>
            <span class="tenue"><?= e(date('d/m H:i', strtotime($a['created_at']))) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
