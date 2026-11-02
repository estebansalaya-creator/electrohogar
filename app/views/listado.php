<?php
/**
 * @var string     $titulo
 * @var array      $migas
 * @var array|null $categoria
 * @var array|null $padre
 * @var array      $subcats
 * @var array      $resultado  items, total, marcas, pagina, paginas
 * @var array      $query
 * @var array      $filtros
 */
$lnk = fn(array $cambios) => url('productos.php', array_merge($query, $cambios));
$total = $resultado['total'];
$hayFiltros = $filtros['marca'] || $filtros['min'] || $filtros['max'] || $filtros['envio'];
$ordenes = [
    'relevancia' => 'Más relevantes',
    'menor'      => 'Menor precio',
    'mayor'      => 'Mayor precio',
    'descuento'  => 'Mayor descuento',
    'nombre'     => 'Nombre (A-Z)',
];
$hidden = function (array $excluir) use ($query): string {
    $html = '';
    foreach ($query as $k => $v) {
        if ($v !== null && $v !== '' && !in_array($k, $excluir, true)) {
            $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
        }
    }
    return $html;
};
?>
<div class="wrap">
  <?php partial('migas', ['migas' => $migas]); ?>

  <div class="listado__head">
    <h1 class="listado__titulo"><?= e($titulo) ?></h1>
    <p class="listado__cant"><?= $total ?> <?= plural($total, 'producto', 'productos') ?></p>
  </div>

  <div class="listado">
    <aside class="filtros" id="filtros" aria-label="Filtros" data-filtros>
      <div class="filtros__head">
        <p class="filtros__titulo">Filtrar</p>
        <button type="button" class="icon-btn" data-filtros-close><?= icono('cerrar') ?><span class="sr-only">Cerrar filtros</span></button>
      </div>

      <?php if ($subcats): ?>
        <div class="filtro">
          <p class="filtro__titulo"><?= e(($padre ?? $categoria)['nombre']) ?></p>
          <ul class="filtro__lista">
            <?php foreach ($subcats as $s): ?>
              <li><a href="<?= e(url_categoria($s['slug'])) ?>"<?= $categoria && $s['id'] === $categoria['id'] ? ' class="is-activo" aria-current="page"' : '' ?>><?= e($s['nombre']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($resultado['marcas']): ?>
        <div class="filtro">
          <p class="filtro__titulo">Marca</p>
          <ul class="filtro__lista">
            <?php foreach ($resultado['marcas'] as $m): $activa = $filtros['marca'] === $m['slug']; ?>
              <li>
                <a class="filtro__check<?= $activa ? ' is-activo' : '' ?>" href="<?= e($lnk(['marca' => $activa ? null : $m['slug']])) ?>"<?= $activa ? ' aria-current="true"' : '' ?>>
                  <span class="check" aria-hidden="true"><?= $activa ? icono('check') : '' ?></span>
                  <?= e($m['nombre']) ?> <span class="filtro__n">(<?= (int) $m['cantidad'] ?>)</span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="filtro">
        <p class="filtro__titulo">Envío</p>
        <a class="filtro__check<?= $filtros['envio'] ? ' is-activo' : '' ?>" href="<?= e($lnk(['envio' => $filtros['envio'] ? null : 1])) ?>">
          <span class="check" aria-hidden="true"><?= $filtros['envio'] ? icono('check') : '' ?></span>
          Envío gratis a CABA y GBA
        </a>
      </div>

      <form class="filtro" method="get" action="<?= e(url('productos.php')) ?>">
        <?= $hidden(['min', 'max']) ?>
        <p class="filtro__titulo" id="f-precio">Precio</p>
        <div class="filtro__rango" role="group" aria-labelledby="f-precio">
          <label><span class="sr-only">Precio mínimo</span><input type="number" name="min" min="0" step="1000" inputmode="numeric" placeholder="Mínimo" value="<?= $filtros['min'] ?: '' ?>"></label>
          <label><span class="sr-only">Precio máximo</span><input type="number" name="max" min="0" step="1000" inputmode="numeric" placeholder="Máximo" value="<?= $filtros['max'] ?: '' ?>"></label>
          <button class="btn btn--sec btn--sm" type="submit">Aplicar</button>
        </div>
      </form>

      <?php if ($hayFiltros): ?>
        <a class="filtros__limpiar" href="<?= e($lnk(['marca' => null, 'min' => null, 'max' => null, 'envio' => null])) ?>">Quitar todos los filtros</a>
      <?php endif; ?>
    </aside>

    <div class="listado__main">
      <div class="toolbar">
        <button type="button" class="btn btn--sec btn--sm toolbar__filtrar" aria-controls="filtros" aria-expanded="false" data-filtros-open><?= icono('filtro') ?> Filtrar</button>

        <?php if ($hayFiltros): ?>
          <ul class="chips" aria-label="Filtros aplicados">
            <?php if ($filtros['marca']):
                $nombreMarca = $filtros['marca'];
                foreach ($resultado['marcas'] as $m) { if ($m['slug'] === $filtros['marca']) { $nombreMarca = $m['nombre']; } } ?>
              <li><a class="chip" href="<?= e($lnk(['marca' => null])) ?>"><?= e($nombreMarca) ?> <?= icono('cerrar') ?><span class="sr-only">Quitar filtro</span></a></li>
            <?php endif; ?>
            <?php if ($filtros['envio']): ?>
              <li><a class="chip" href="<?= e($lnk(['envio' => null])) ?>">Envío gratis <?= icono('cerrar') ?><span class="sr-only">Quitar filtro</span></a></li>
            <?php endif; ?>
            <?php if ($filtros['min'] || $filtros['max']): ?>
              <li><a class="chip" href="<?= e($lnk(['min' => null, 'max' => null])) ?>">
                <?= $filtros['min'] ? 'Desde ' . money($filtros['min']) : '' ?><?= $filtros['min'] && $filtros['max'] ? ' ' : '' ?><?= $filtros['max'] ? 'Hasta ' . money($filtros['max']) : '' ?>
                <?= icono('cerrar') ?><span class="sr-only">Quitar filtro</span></a></li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>

        <form class="orden" method="get" action="<?= e(url('productos.php')) ?>">
          <?= $hidden(['orden']) ?>
          <label for="orden">Ordenar por</label>
          <select id="orden" name="orden" data-autosubmit>
            <?php foreach ($ordenes as $k => $txt): ?>
              <option value="<?= e($k) ?>"<?= $filtros['orden'] === $k ? ' selected' : '' ?>><?= e($txt) ?></option>
            <?php endforeach; ?>
          </select>
          <noscript><button class="btn btn--sec btn--sm" type="submit">Ordenar</button></noscript>
        </form>
      </div>

      <?php if ($resultado['items']): ?>
        <div class="grid grid--listado">
          <?php foreach ($resultado['items'] as $p) { partial('tarjeta-producto', ['p' => $p]); } ?>
        </div>
        <?php partial('paginacion', ['pagina' => $resultado['pagina'], 'paginas' => $resultado['paginas'], 'query' => $query]); ?>
      <?php else: ?>
        <div class="vacio">
          <p class="vacio__titulo">No encontramos productos<?= $filtros['q'] !== '' ? ' para “' . e($filtros['q']) . '”' : '' ?><?= $hayFiltros ? ' con esos filtros' : '' ?>.</p>
          <?php if ($hayFiltros): ?>
            <p>Probá sacando algún filtro.</p>
            <a class="btn btn--primario" href="<?= e($lnk(['marca' => null, 'min' => null, 'max' => null, 'envio' => null])) ?>">Quitar filtros</a>
          <?php else: ?>
            <p>Revisá cómo está escrito o buscá algo más general, por ejemplo “heladera” o “aire”. También podés consultarnos por WhatsApp.</p>
            <a class="btn btn--primario" href="<?= e(whatsapp_link('Hola, estoy buscando: ' . $filtros['q'])) ?>" target="_blank" rel="noopener"><?= icono('whatsapp') ?> Consultar por WhatsApp</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
