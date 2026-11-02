<?php
/**
 * @var array  $ent
 * @var string $buscar
 * @var string $filtro
 * @var array  $res   items, total, pagina, paginas
 */
$base = ['e' => $ent['clave'], 'q' => $buscar ?: null, 'filtro' => $filtro ?: null];
?>
<div class="encabezado">
  <h1><?= e($ent['titulo']) ?> <span class="encabezado__cant"><?= $res['total'] ?></span></h1>
  <div class="encabezado__acciones">
    <?php if ($ent['exportar']): ?>
      <a class="btn btn--sec" href="<?= e(admin_url('exportar.php', ['e' => $ent['clave']])) ?>">Descargar para Excel</a>
    <?php endif; ?>
    <?php if ($ent['crear']): ?>
      <a class="btn btn--primario" href="<?= e(admin_url('editar.php', ['e' => $ent['clave']])) ?>"><?= e($ent['nueva']) ?></a>
    <?php endif; ?>
  </div>
</div>

<?php if ($ent['buscar'] || $ent['filtro']): ?>
<form class="filtros" method="get">
  <input type="hidden" name="e" value="<?= e($ent['clave']) ?>">
  <?php if ($ent['buscar']): ?>
    <label class="sr-only" for="q">Buscar</label>
    <input id="q" name="q" type="search" value="<?= e($buscar) ?>" placeholder="Buscar…">
  <?php endif; ?>
  <?php if ($ent['filtro']): $campoFiltro = $ent['campos'][$ent['filtro']]; ?>
    <label class="sr-only" for="filtro"><?= e($campoFiltro['label']) ?></label>
    <select id="filtro" name="filtro" data-autosubmit>
      <option value="">Todos los estados</option>
      <?php foreach (crud_opciones($campoFiltro) as $v => $t): ?>
        <option value="<?= e($v) ?>"<?= $filtro === (string) $v ? ' selected' : '' ?>><?= e($t) ?></option>
      <?php endforeach; ?>
    </select>
  <?php endif; ?>
  <button class="btn btn--sec" type="submit">Filtrar</button>
  <?php if ($buscar !== '' || $filtro !== ''): ?>
    <a class="link" href="<?= e(admin_url('lista.php', ['e' => $ent['clave']])) ?>">Ver todos</a>
  <?php endif; ?>
</form>
<?php endif; ?>

<?php if (!$res['items']): ?>
  <div class="tarjeta vacio">
    <p><?= $buscar !== '' || $filtro !== '' ? 'No hay resultados con esa búsqueda.' : 'Todavía no hay nada cargado.' ?></p>
    <?php if ($ent['crear'] && $buscar === ''): ?>
      <a class="btn btn--primario" href="<?= e(admin_url('editar.php', ['e' => $ent['clave']])) ?>"><?= e($ent['nueva']) ?></a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="tabla-contenedor tarjeta">
    <table class="tabla">
      <thead>
        <tr>
          <?php foreach ($ent['columnas'] as $col => $titulo): ?>
            <th scope="col"<?= $titulo === '' ? ' class="col-img"' : '' ?>><?= $titulo === '' ? '<span class="sr-only">Imagen</span>' : e($titulo) ?></th>
          <?php endforeach; ?>
          <th scope="col" class="col-acciones"><span class="sr-only">Acciones</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($res['items'] as $fila): ?>
          <tr>
            <?php $primera = true; foreach ($ent['columnas'] as $col => $titulo): ?>
              <td<?= $titulo === '' ? ' class="col-img"' : '' ?>>
                <?php if ($primera && $titulo !== '' && $ent['editar']): $primera = false; ?>
                  <a class="tabla__principal" href="<?= e(admin_url('editar.php', ['e' => $ent['clave'], 'id' => $fila['id']])) ?>"><?= crud_celda($ent, $col, $fila) ?></a>
                <?php else: if ($titulo !== '') { $primera = false; } ?>
                  <?= crud_celda($ent, $col, $fila) ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
            <td class="col-acciones">
              <?php if ($ent['ver']): ?>
                <a class="link" href="<?= e(($ent['ver'])($fila)) ?>" target="_blank" rel="noopener">Ver</a>
              <?php endif; ?>
              <?php if ($ent['editar']): ?>
                <a class="link" href="<?= e(admin_url('editar.php', ['e' => $ent['clave'], 'id' => $fila['id']])) ?>">Editar</a>
              <?php endif; ?>
              <?php if ($ent['borrar'] !== false): ?>
                <form method="post" action="<?= e(admin_url('borrar.php')) ?>" class="en-linea" data-confirmar="¿Borrar este registro? No se puede deshacer.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="e" value="<?= e($ent['clave']) ?>">
                  <input type="hidden" name="id" value="<?= (int) $fila['id'] ?>">
                  <button type="submit" class="link link--peligro">Borrar</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($res['paginas'] > 1): ?>
    <nav class="paginas" aria-label="Páginas">
      <?php for ($n = 1; $n <= $res['paginas']; $n++): ?>
        <?php if ($n === $res['pagina']): ?>
          <span aria-current="page"><?= $n ?></span>
        <?php else: ?>
          <a href="<?= e(admin_url('lista.php', $base + ['pagina' => $n])) ?>"><?= $n ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
