<?php
/** @var array $p */
$off = descuento_pct($p);
$cuotas = (int) $p['cuotas_sin_interes'];
$sinStock = (int) $p['stock'] <= 0;
$href = url_producto($p);
?>
<article class="card<?= $sinStock ? ' card--agotado' : '' ?>">
  <a class="card__media" href="<?= e($href) ?>" tabindex="-1" aria-hidden="true">
    <img src="<?= e(img($p['imagen'])) ?>" alt="" width="400" height="400" loading="lazy" decoding="async">
  </a>
  <div class="card__flags">
    <?php if ($off > 0): ?><span class="flag flag--off"><?= $off ?>% OFF</span><?php endif; ?>
    <?php if ((int) $p['envio_gratis'] === 1): ?><span class="flag flag--envio">Envío gratis</span><?php endif; ?>
  </div>
  <div class="card__body">
    <?php if (!empty($p['marca'])): ?><p class="card__marca"><?= e($p['marca']) ?></p><?php endif; ?>
    <h3 class="card__nombre"><a href="<?= e($href) ?>"><?= e($p['nombre']) ?></a></h3>

    <div class="card__precios">
      <?php if ($off > 0): ?><s class="precio-lista"><span class="sr-only">Antes</span><?= money($p['precio_lista']) ?></s><?php endif; ?>
      <p class="precio"><?= money($p['precio']) ?></p>
      <?php if (descuento_transferencia() > 0): ?>
        <p class="transf"><span class="etiqueta"><strong><?= money(precio_transferencia((float) $p['precio'])) ?></strong></span> <span class="transf__txt">con transferencia</span></p>
      <?php endif; ?>
      <?php if ($cuotas > 1): ?>
        <p class="cuotas"><?= $cuotas ?> cuotas sin interés de <?= money_cuota(valor_cuota((float) $p['precio'], $cuotas)) ?></p>
      <?php endif; ?>
    </div>

    <?php if ($sinStock): ?>
      <p class="card__agotado">Sin stock</p>
    <?php else: ?>
      <form class="card__comprar" action="<?= e(url('carrito.php')) ?>" method="post" data-add-to-cart>
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="agregar">
        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
        <input type="hidden" name="cantidad" value="1">
        <button class="btn btn--primario btn--block" type="submit">Agregar al carrito</button>
      </form>
    <?php endif; ?>
  </div>
</article>
