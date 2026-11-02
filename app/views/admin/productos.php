<?php
/**
 * @var array  $items
 * @var int    $total
 * @var int    $pagina
 * @var int    $paginas
 * @var string $q
 * @var int    $cat
 * @var string $estado
 * @var array  $arbol
 * @var string $volverA
 */
$estados = [
    ''           => 'Todos',
    'activos'    => 'Publicados',
    'inactivos'  => 'Ocultos',
    'sin_stock'  => 'Sin stock',
    'poco_stock' => 'Poco stock (1 a 3)',
    'destacados' => 'En "Lo más buscado"',
    'ofertas'    => 'Con descuento',
    'sin_foto'   => 'Sin foto real',
];
$base = array_filter(['q' => $q, 'cat' => $cat ?: null, 'estado' => $estado]);
?>
<div class="encabezado">
  <h1>Productos <span class="encabezado__cant"><?= $total ?></span></h1>
  <div class="encabezado__acciones">
    <a class="btn btn--sec" href="<?= e(admin_url('importar.php')) ?>">Importar desde Excel</a>
    <a class="btn btn--primario" href="<?= e(admin_url('producto.php')) ?>">Nuevo producto</a>
  </div>
</div>

<form class="filtros" method="get">
  <label class="sr-only" for="q">Buscar</label>
  <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Nombre, código o marca…">
  <label class="sr-only" for="cat">Categoría</label>
  <select id="cat" name="cat" data-autosubmit>
    <option value="">Todas las categorías</option>
    <?php foreach ($arbol as $r): ?>
      <optgroup label="<?= e($r['nombre']) ?>">
        <option value="<?= (int) $r['id'] ?>"<?= $cat === (int) $r['id'] ? ' selected' : '' ?>>Todo <?= e($r['nombre']) ?></option>
        <?php foreach ($r['hijos'] as $h): ?>
          <option value="<?= (int) $h['id'] ?>"<?= $cat === (int) $h['id'] ? ' selected' : '' ?>><?= e($h['nombre']) ?></option>
        <?php endforeach; ?>
      </optgroup>
    <?php endforeach; ?>
  </select>
  <label class="sr-only" for="estado">Estado</label>
  <select id="estado" name="estado" data-autosubmit>
    <?php foreach ($estados as $k => $t): ?>
      <option value="<?= e($k) ?>"<?= $estado === $k ? ' selected' : '' ?>><?= e($t) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn--sec" type="submit">Filtrar</button>
  <?php if ($base): ?><a class="link" href="<?= e(admin_url('productos.php')) ?>">Ver todos</a><?php endif; ?>
</form>

<?php if (!$items): ?>
  <div class="tarjeta vacio">
    <p><?= $base ? 'No hay productos con esos filtros.' : 'Todavía no hay productos.' ?></p>
    <a class="btn btn--primario" href="<?= e(admin_url('producto.php')) ?>">Nuevo producto</a>
  </div>
<?php else: ?>
  <div class="tabla-contenedor tarjeta">
    <table class="tabla tabla--productos">
      <thead>
        <tr>
          <th scope="col" class="col-img"><span class="sr-only">Foto</span></th>
          <th scope="col">Producto</th>
          <th scope="col">Categoría</th>
          <th scope="col">Precio y stock</th>
          <th scope="col">Publicado</th>
          <th scope="col">Destacado</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $p): $off = descuento_pct($p); ?>
          <tr class="<?= $p['activo'] ? '' : 'fila--apagada' ?>">
            <td class="col-img"><img class="miniatura" src="<?= e(img($p['imagen'])) ?>" alt="" width="44" height="44" loading="lazy"></td>
            <td>
              <a class="tabla__principal" href="<?= e(admin_url('producto.php', ['id' => $p['id']])) ?>"><?= e($p['nombre']) ?></a>
              <span class="tabla__sub"><?= e($p['sku']) ?><?= $p['marca'] ? ' · ' . e($p['marca']) : '' ?><?= $off ? ' · <span class="txt-naranja">' . $off . '% OFF</span>' : '' ?></span>
            </td>
            <td><?= e($p['categoria']) ?></td>
            <td>
              <form method="post" class="rapido" aria-label="Precio y stock de <?= e($p['nombre']) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="rapido">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <label><span class="sr-only">Precio</span><span aria-hidden="true">$</span>
                  <input name="precio" inputmode="decimal" value="<?= e(number_format((float) $p['precio'], 0, '', '')) ?>" size="9"></label>
                <label><span class="sr-only">Stock</span>
                  <input name="stock" inputmode="numeric" value="<?= (int) $p['stock'] ?>" size="3" class="<?= $p['stock'] <= 0 ? 'stock-cero' : ($p['stock'] <= 3 ? 'stock-poco' : '') ?>"> u.</label>
                <button type="submit" class="btn btn--chico">Guardar</button>
              </form>
            </td>
            <td>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="alternar"><input type="hidden" name="campo" value="activo">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button type="submit" class="interruptor<?= $p['activo'] ? ' is-on' : '' ?>" aria-pressed="<?= $p['activo'] ? 'true' : 'false' ?>">
                  <span class="sr-only">Publicado</span><?= $p['activo'] ? 'Sí' : 'No' ?></button>
              </form>
            </td>
            <td>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="alternar"><input type="hidden" name="campo" value="destacado">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button type="submit" class="interruptor<?= $p['destacado'] ? ' is-on' : '' ?>" aria-pressed="<?= $p['destacado'] ? 'true' : 'false' ?>">
                  <span class="sr-only">Destacado</span><?= $p['destacado'] ? 'Sí' : 'No' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($paginas > 1): ?>
    <nav class="paginas" aria-label="Páginas">
      <?php for ($n = 1; $n <= $paginas; $n++): ?>
        <?php if ($n === $pagina): ?><span aria-current="page"><?= $n ?></span>
        <?php else: ?><a href="<?= e(admin_url('productos.php', $base + ['pagina' => $n])) ?>"><?= $n ?></a><?php endif; ?>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
