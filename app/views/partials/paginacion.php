<?php
/**
 * @var int   $pagina
 * @var int   $paginas
 * @var array $query  parámetros actuales del listado
 */
if ($paginas <= 1) {
    return;
}
$link = fn(int $n) => url('productos.php', ['pagina' => $n > 1 ? $n : null] + $query);
$desde = max(1, $pagina - 2);
$hasta = min($paginas, $pagina + 2);
?>
<nav class="paginacion" aria-label="Páginas">
  <?php if ($pagina > 1): ?>
    <a class="paginacion__btn" href="<?= e($link($pagina - 1)) ?>" rel="prev"><?= icono('izq') ?><span class="sr-only">Página anterior</span></a>
  <?php endif; ?>
  <?php if ($desde > 1): ?>
    <a class="paginacion__btn" href="<?= e($link(1)) ?>">1</a>
    <?php if ($desde > 2): ?><span class="paginacion__gap">…</span><?php endif; ?>
  <?php endif; ?>
  <?php for ($n = $desde; $n <= $hasta; $n++): ?>
    <?php if ($n === $pagina): ?>
      <span class="paginacion__btn is-actual" aria-current="page"><?= $n ?></span>
    <?php else: ?>
      <a class="paginacion__btn" href="<?= e($link($n)) ?>"><?= $n ?></a>
    <?php endif; ?>
  <?php endfor; ?>
  <?php if ($hasta < $paginas): ?>
    <?php if ($hasta < $paginas - 1): ?><span class="paginacion__gap">…</span><?php endif; ?>
    <a class="paginacion__btn" href="<?= e($link($paginas)) ?>"><?= $paginas ?></a>
  <?php endif; ?>
  <?php if ($pagina < $paginas): ?>
    <a class="paginacion__btn" href="<?= e($link($pagina + 1)) ?>" rel="next"><?= icono('der') ?><span class="sr-only">Página siguiente</span></a>
  <?php endif; ?>
</nav>
