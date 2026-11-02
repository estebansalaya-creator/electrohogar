<?php
/**
 * @var array  $items
 * @var int    $total
 * @var int    $pagina
 * @var int    $paginas
 * @var string $q
 * @var string $estado
 * @var array  $conteo  estado => cantidad
 */
$base = array_filter(['q' => $q, 'estado' => $estado]);
$abiertos = array_sum(array_intersect_key($conteo, array_flip(['nuevo', 'confirmado', 'preparando', 'enviado'])));
?>
<div class="encabezado">
  <h1>Pedidos <span class="encabezado__cant"><?= $total ?></span></h1>
</div>

<form class="filtros" method="get">
  <label class="sr-only" for="q">Buscar</label>
  <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Número, nombre, teléfono o email…">
  <label class="sr-only" for="estado">Estado</label>
  <select id="estado" name="estado" data-autosubmit>
    <option value="">Todos los estados</option>
    <option value="abiertos"<?= $estado === 'abiertos' ? ' selected' : '' ?>>Sin terminar (<?= $abiertos ?>)</option>
    <?php foreach (PEDIDO_ESTADOS as $k => $t): ?>
      <option value="<?= e($k) ?>"<?= $estado === $k ? ' selected' : '' ?>><?= e($t) ?> (<?= (int) ($conteo[$k] ?? 0) ?>)</option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn--sec" type="submit">Filtrar</button>
  <?php if ($base): ?><a class="link" href="<?= e(admin_url('pedidos.php')) ?>">Ver todos</a><?php endif; ?>
</form>

<?php if (!$items): ?>
  <div class="tarjeta vacio">
    <p><?= $base ? 'No hay pedidos con esos filtros.' : 'Todavía no entró ningún pedido. Aparecen acá cuando un cliente toca "Finalizar pedido" en la tienda.' ?></p>
  </div>
<?php else: ?>
  <div class="tabla-contenedor tarjeta">
    <table class="tabla tabla--pedidos">
      <thead>
        <tr>
          <th scope="col">N°</th>
          <th scope="col">Cliente</th>
          <th scope="col" class="col-entrega">Entrega</th>
          <th scope="col" class="num">Total</th>
          <th scope="col">Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $p): ?>
          <tr class="<?= $p['estado'] === 'cancelado' ? 'fila--apagada' : '' ?>">
            <td>
              <a class="tabla__principal" href="<?= e(admin_url('pedido.php', ['id' => $p['id']])) ?>"><?= e(pedido_numero($p)) ?></a>
              <span class="tabla__sub"><?= e(date('d/m/Y H:i', strtotime($p['created_at']))) ?></span>
            </td>
            <td>
              <?= e($p['nombre']) ?>
              <span class="tabla__sub"><?= e($p['telefono']) ?></span>
            </td>
            <td class="col-entrega">
              <?= $p['entrega'] === 'retiro' ? 'Retira en el local' : e(trim($p['localidad'] . ($p['cp'] ? ' (' . $p['cp'] . ')' : ''))) ?>
              <span class="tabla__sub"><?= (int) $p['unidades'] ?> <?= plural((int) $p['unidades'], 'producto', 'productos') ?></span>
            </td>
            <td class="num">
              <?= money($p['total']) ?>
              <?php if ($p['costo_envio'] === null): ?><span class="tabla__sub">+ envío a cotizar</span><?php endif; ?>
            </td>
            <td><span class="estado estado--<?= e($p['estado']) ?>"><?= e(PEDIDO_ESTADOS[$p['estado']] ?? $p['estado']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($paginas > 1): ?>
    <nav class="paginas" aria-label="Páginas">
      <?php for ($n = 1; $n <= $paginas; $n++): ?>
        <?php if ($n === $pagina): ?><span aria-current="page"><?= $n ?></span>
        <?php else: ?><a href="<?= e(admin_url('pedidos.php', $base + ['pagina' => $n])) ?>"><?= $n ?></a><?php endif; ?>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
