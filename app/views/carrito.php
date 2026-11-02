<?php
/**
 * @var array       $r          resumen del carrito
 * @var array       $sugeridos
 */
$descTransf = descuento_transferencia();
?>
<div class="wrap">
  <?php partial('migas', ['migas' => [['Carrito', null]]]); ?>
  <h1 class="pagina__titulo">Tu carrito</h1>

  <?php if (!$r['lineas']): ?>
    <div class="vacio">
      <p class="vacio__titulo">Todavía no agregaste productos.</p>
      <p>Mirá lo más buscado o recorré las categorías.</p>
      <a class="btn btn--primario" href="<?= e(url('productos.php')) ?>">Ver productos</a>
    </div>
    <?php if ($sugeridos): ?>
      <section class="seccion" aria-labelledby="t-sug">
        <div class="seccion__head"><h2 class="seccion__titulo" id="t-sug">Lo más buscado</h2></div>
        <div class="grid">
          <?php foreach ($sugeridos as $p) { partial('tarjeta-producto', ['p' => $p]); } ?>
        </div>
      </section>
    <?php endif; ?>
  <?php else: ?>

  <div class="carrito">
    <div class="carrito__items">
      <ul class="lineas">
        <?php foreach ($r['lineas'] as $l): $p = $l['producto']; ?>
          <li class="linea">
            <a class="linea__img" href="<?= e(url_producto($p)) ?>" tabindex="-1" aria-hidden="true">
              <img src="<?= e(img($p['imagen'])) ?>" alt="" width="96" height="96" loading="lazy">
            </a>
            <div class="linea__info">
              <?php if ($p['marca']): ?><p class="card__marca"><?= e($p['marca']) ?></p><?php endif; ?>
              <a class="linea__nombre" href="<?= e(url_producto($p)) ?>"><?= e($p['nombre']) ?></a>
              <p class="linea__unit"><?= money($p['precio']) ?> c/u</p>
              <?php if ((int) $p['envio_gratis'] === 1): ?><p class="linea__envio txt-verde">Envío gratis a CABA y GBA</p><?php endif; ?>
            </div>
            <form class="linea__cant" action="<?= e(url('carrito.php')) ?>" method="post" data-stepper data-autosubmit-stepper>
              <?= csrf_field() ?>
              <input type="hidden" name="accion" value="actualizar">
              <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <div class="cantidad cantidad--sm">
                <button type="button" data-step="-1" aria-label="Restar una unidad de <?= e($p['nombre']) ?>"><?= icono('menos') ?></button>
                <label class="sr-only" for="cant-<?= (int) $p['id'] ?>">Cantidad de <?= e($p['nombre']) ?></label>
                <input id="cant-<?= (int) $p['id'] ?>" name="cantidad" type="number" value="<?= $l['cantidad'] ?>" min="1" max="<?= $l['max'] ?>" inputmode="numeric">
                <button type="button" data-step="1" aria-label="Sumar una unidad de <?= e($p['nombre']) ?>"><?= icono('mas') ?></button>
              </div>
              <noscript><button class="btn btn--sec btn--sm" type="submit">Actualizar</button></noscript>
            </form>
            <p class="linea__subtotal"><?= money($l['subtotal']) ?></p>
            <form class="linea__quitar" action="<?= e(url('carrito.php')) ?>" method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="accion" value="quitar">
              <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <button class="icon-btn" type="submit"><?= icono('basura') ?><span class="sr-only">Sacar <?= e($p['nombre']) ?> del carrito</span></button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="carrito__acciones">
        <a class="btn btn--link" href="<?= e(url('productos.php')) ?>"><?= icono('izq') ?> Seguir comprando</a>
        <form action="<?= e(url('carrito.php')) ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="vaciar">
          <button class="btn btn--link btn--peligro" type="submit">Vaciar carrito</button>
        </form>
      </div>
    </div>

    <aside class="resumen" aria-labelledby="t-resumen">
      <h2 class="resumen__titulo" id="t-resumen">Resumen</h2>

      <form class="resumen__form" action="<?= e(url('carrito.php')) ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="envio">
        <label for="cp-carrito"><?= icono('camion') ?> Envío</label>
        <div class="envio__fila">
          <input id="cp-carrito" name="cp" type="text" inputmode="numeric" maxlength="8" placeholder="Código postal" value="<?= $r['cp'] ? (int) $r['cp'] : '' ?>" autocomplete="postal-code">
          <button class="btn btn--sec btn--sm" type="submit">Calcular</button>
        </div>
        <?php if ($r['envio']): ?>
          <?php if ($r['envio']['ok']): ?>
            <p class="resumen__nota"><?= e($r['envio']['zona']) ?>: <?= $r['envio']['gratis'] ? '<strong class="txt-verde">gratis</strong>' : money($r['envio']['costo']) ?>. Llega en <?= e($r['envio']['dias']) ?>.</p>
          <?php else: ?>
            <p class="resumen__nota resumen__nota--error"><?= e($r['envio']['mensaje']) ?></p>
          <?php endif; ?>
        <?php endif; ?>
      </form>

      <?php if ($r['cupon']): ?>
        <form class="resumen__form resumen__cupon-ok" action="<?= e(url('carrito.php')) ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="quitar_cupon">
          <p><?= icono('cupon') ?> Cupón <strong><?= e($r['cupon']['codigo']) ?></strong> aplicado</p>
          <button class="btn btn--link btn--sm" type="submit">Quitar</button>
        </form>
      <?php else: ?>
        <form class="resumen__form" action="<?= e(url('carrito.php')) ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="cupon">
          <label for="cupon"><?= icono('cupon') ?> Cupón de descuento</label>
          <div class="envio__fila">
            <input id="cupon" name="cupon" type="text" maxlength="30" placeholder="Código" autocapitalize="characters">
            <button class="btn btn--sec btn--sm" type="submit">Aplicar</button>
          </div>
        </form>
      <?php endif; ?>

      <dl class="totales">
        <div><dt>Subtotal (<?= $r['cantidad'] ?> <?= plural($r['cantidad'], 'producto', 'productos') ?>)</dt><dd><?= money($r['subtotal']) ?></dd></div>
        <?php if ($r['descuento'] > 0): ?>
          <div class="txt-verde"><dt>Descuento cupón</dt><dd>-<?= money($r['descuento']) ?></dd></div>
        <?php endif; ?>
        <div><dt>Envío</dt><dd><?php
            if (!$r['envio']) { echo 'Ingresá tu CP'; }
            elseif (!$r['envio']['ok']) { echo 'A cotizar'; }
            elseif ($r['envio']['gratis']) { echo '<span class="txt-verde">Gratis</span>'; }
            else { echo money($r['envio']['costo']); }
        ?></dd></div>
        <div class="totales__total"><dt>Total</dt><dd><?= money($r['total']) ?></dd></div>
      </dl>
      <p class="resumen__sinimp">PRECIO SIN IMPUESTOS NACIONALES (productos): <?= money($r['sin_impuestos'], 2) ?></p>

      <?php if ($descTransf > 0): ?>
        <div class="etiqueta etiqueta--lg">
          <strong><?= money($r['total_transferencia']) ?></strong>
          <span>pagando con transferencia o depósito</span>
        </div>
      <?php endif; ?>
      <?php if ($r['cuotas'] > 1): ?>
        <p class="cuotas"><?= icono('tarjeta') ?> O hasta <?= $r['cuotas'] ?> cuotas sin interés de <?= money_cuota($r['valor_cuota']) ?>. CFT: 0,00%.</p>
      <?php endif; ?>

      <a class="btn btn--primario btn--lg btn--block" href="<?= e(url('checkout.php')) ?>">Finalizar pedido</a>
      <p class="resumen__nota">Completás tus datos y nos lo mandás por WhatsApp. Todavía no pagás nada: te confirmamos stock, forma de pago y entrega.</p>
    </aside>
  </div>
  <?php endif; ?>
</div>
