<?php
/**
 * @var int         $codigo
 * @var string      $titulo
 * @var string      $texto
 * @var string|null $detalle
 */
?>
<div class="wrap wrap--angosto">
  <div class="vacio vacio--error">
    <p class="vacio__codigo"><?= (int) $codigo ?></p>
    <h1 class="vacio__titulo"><?= e($titulo) ?></h1>
    <p><?= e($texto) ?></p>
    <div class="vacio__acciones">
      <a class="btn btn--primario" href="<?= e(url('')) ?>">Ir al inicio</a>
      <a class="btn btn--sec" href="<?= e(url('productos.php')) ?>">Ver productos</a>
    </div>
    <?php if ($detalle): ?><pre class="vacio__detalle"><?= e($detalle) ?></pre><?php endif; ?>
  </div>
</div>
