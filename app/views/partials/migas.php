<?php
/** @var array $migas [['Texto', 'url'|null], ...] */
?>
<nav class="migas" aria-label="Estás en">
  <ol>
    <li><a href="<?= e(url('')) ?>">Inicio</a></li>
    <?php foreach ($migas as $i => [$texto, $href]): ?>
      <li>
        <?php if ($href && $i < count($migas) - 1): ?>
          <a href="<?= e($href) ?>"><?= e($texto) ?></a>
        <?php else: ?>
          <span aria-current="page"><?= e($texto) ?></span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>
