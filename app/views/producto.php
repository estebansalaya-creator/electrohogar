<?php
/**
 * @var array    $p
 * @var array    $migas
 * @var array    $imagenes
 * @var array    $specs
 * @var array    $relacionados
 * @var int|null $cpGuardado
 */
$precio = (float) $p['precio'];
$off = descuento_pct($p);
$cuotas = (int) $p['cuotas_sin_interes'];
$stock = (int) $p['stock'];
$maxUnidades = carrito_max_unidades($p);
$transf = precio_transferencia($precio);
$descTransf = descuento_transferencia();
?>
<div class="wrap">
  <?php partial('migas', ['migas' => $migas]); ?>

  <div class="ficha">
    <div class="galeria" data-galeria>
      <div class="galeria__principal">
        <img src="<?= e(img($imagenes[0])) ?>" alt="<?= e($p['nombre']) ?>" width="600" height="600" data-galeria-img fetchpriority="high">
        <?php if ($off > 0): ?><span class="flag flag--off flag--lg"><?= $off ?>% OFF</span><?php endif; ?>
      </div>
      <?php if (count($imagenes) > 1): ?>
        <ul class="galeria__miniaturas">
          <?php foreach ($imagenes as $i => $ruta): ?>
            <li><button type="button" data-galeria-thumb="<?= e(img($ruta)) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>>
              <img src="<?= e(img($ruta)) ?>" alt="Foto <?= $i + 1 ?>" width="80" height="80" loading="lazy">
            </button></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="ficha__info">
      <?php if ($p['marca']): ?>
        <a class="ficha__marca" href="<?= e(url('productos.php', ['q' => $p['marca']])) ?>"><?= e($p['marca']) ?></a>
      <?php endif; ?>
      <h1 class="ficha__nombre"><?= e($p['nombre']) ?></h1>
      <p class="ficha__meta">
        <span>Código <?= e($p['sku']) ?></span>
        <?php if ($stock <= 0): ?>
          <span class="stock stock--sin">Sin stock</span>
        <?php elseif ($stock <= 3): ?>
          <span class="stock stock--poco">Últimas <?= $stock ?> <?= plural($stock, 'unidad', 'unidades') ?></span>
        <?php else: ?>
          <span class="stock">Stock disponible</span>
        <?php endif; ?>
      </p>

      <div class="ficha__precios">
        <?php if ($off > 0): ?>
          <p class="ficha__lista"><s><span class="sr-only">Antes</span><?= money($p['precio_lista']) ?></s> <span class="ficha__ahorro">Ahorrás <?= money((float) $p['precio_lista'] - $precio) ?></span></p>
        <?php endif; ?>
        <p class="precio precio--xl"><?= money($precio) ?></p>
        <p class="ficha__sinimp">PRECIO SIN IMPUESTOS NACIONALES: <?= money(precio_sin_impuestos($precio, (float) $p['iva']), 2) ?></p>

        <?php if ($descTransf > 0): ?>
          <div class="etiqueta etiqueta--lg">
            <strong><?= money($transf) ?></strong>
            <span>pagando con transferencia o depósito (<?= e(rtrim(rtrim(number_format($descTransf, 1, ',', ''), '0'), ',')) ?>% menos)</span>
          </div>
        <?php endif; ?>

        <?php if ($cuotas > 1): ?>
          <div class="ficha__cuotas">
            <p class="cuotas cuotas--lg"><?= icono('tarjeta') ?> <strong><?= $cuotas ?> cuotas sin interés de <?= money_cuota(valor_cuota($precio, $cuotas)) ?></strong></p>
            <p class="ficha__cft">Precio contado <?= money($precio) ?>. Total financiado <?= money($precio) ?>. CFT: 0,00%. TEA: 0,00%. Con tarjetas de crédito bancarias seleccionadas.</p>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($maxUnidades > 0): ?>
        <form class="ficha__comprar" action="<?= e(url('carrito.php')) ?>" method="post" data-add-to-cart>
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="agregar">
          <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <div class="cantidad" data-stepper>
            <button type="button" data-step="-1" aria-label="Restar una unidad"><?= icono('menos') ?></button>
            <label class="sr-only" for="cantidad">Cantidad</label>
            <input id="cantidad" name="cantidad" type="number" value="1" min="1" max="<?= $maxUnidades ?>" inputmode="numeric">
            <button type="button" data-step="1" aria-label="Sumar una unidad"><?= icono('mas') ?></button>
          </div>
          <button class="btn btn--primario btn--lg" type="submit"><?= icono('carrito') ?> Agregar al carrito</button>
        </form>
      <?php else: ?>
        <p class="ficha__agotado">Este producto no tiene stock ahora. Escribinos y te avisamos cuando ingrese.</p>
      <?php endif; ?>
      <a class="btn btn--whatsapp" href="<?= e(whatsapp_link('Hola, quiero consultar por: ' . $p['nombre'] . ' (código ' . $p['sku'] . ')')) ?>" target="_blank" rel="noopener">
        <?= icono('whatsapp') ?> Consultar por WhatsApp
      </a>

      <form class="envio" action="<?= e(url('api/envio.php')) ?>" method="get" data-envio>
        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
        <label class="envio__label" for="cp"><?= icono('camion') ?> Calculá el envío</label>
        <div class="envio__fila">
          <input id="cp" name="cp" type="text" inputmode="numeric" maxlength="8" placeholder="Tu código postal" value="<?= $cpGuardado ? (int) $cpGuardado : '' ?>" autocomplete="postal-code">
          <button class="btn btn--sec" type="submit">Calcular</button>
        </div>
        <p class="envio__resultado" data-envio-resultado role="status">
          <?php if ((int) $p['envio_gratis'] === 1): ?>Envío gratis a CABA y GBA.<?php endif; ?>
        </p>
        <a class="envio__ayuda" href="https://www.correoargentino.com.ar/formularios/cpa" target="_blank" rel="noopener">No sé mi código postal</a>
      </form>

      <ul class="ficha__beneficios">
        <li><?= icono('tienda') ?> Retiro sin cargo por nuestro local</li>
        <li><?= icono('escudo') ?> Garantía oficial y factura</li>
        <li><?= icono('devolucion') ?> 10 días para arrepentirte de la compra</li>
      </ul>
    </div>
  </div>

  <div class="ficha__detalle">
    <?php if ($p['descripcion']): ?>
      <section class="ficha__bloque" aria-labelledby="t-desc">
        <h2 class="ficha__subtitulo" id="t-desc">Descripción</h2>
        <div class="texto"><p><?= nl2br(e($p['descripcion'])) ?></p></div>
      </section>
    <?php endif; ?>
    <?php if ($specs): ?>
      <section class="ficha__bloque" aria-labelledby="t-specs">
        <h2 class="ficha__subtitulo" id="t-specs">Características</h2>
        <table class="specs">
          <tbody>
            <?php foreach ($specs as $s): ?>
              <tr><th scope="row"><?= e($s['nombre']) ?></th><td><?= e($s['valor']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    <?php endif; ?>
  </div>

  <?php if ($relacionados): ?>
    <section class="seccion" aria-labelledby="t-rel">
      <div class="seccion__head">
        <h2 class="seccion__titulo" id="t-rel">También te puede interesar</h2>
      </div>
      <div class="grid">
        <?php foreach ($relacionados as $r) { partial('tarjeta-producto', ['p' => $r]); } ?>
      </div>
    </section>
  <?php endif; ?>
</div>
