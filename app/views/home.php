<?php
/**
 * @var array $banners
 * @var array $destacados
 * @var array $accesos
 * @var array $secciones
 */
$descTransf = descuento_transferencia();
?>
<h1 class="sr-only"><?= e(tienda('nombre')) ?>: electrodomésticos, climatización y descanso</h1>

<?php if ($banners): ?>
<section class="hero" aria-roledescription="carrusel" aria-label="Promociones" data-slider>
  <div class="wrap hero__wrap">
    <div class="hero__track">
      <?php foreach ($banners as $i => $b): ?>
        <article class="slide slide--<?= e($b['tema']) ?>" data-slide<?= $i > 0 ? ' hidden' : '' ?>
                 aria-roledescription="diapositiva" aria-label="<?= $i + 1 ?> de <?= count($banners) ?>">
          <div class="slide__texto">
            <h2 class="slide__titulo"><?= e($b['titulo']) ?></h2>
            <?php if ($b['subtitulo']): ?><p class="slide__sub"><?= e($b['subtitulo']) ?></p><?php endif; ?>
            <?php if ($b['texto_boton'] && $b['url']): ?>
              <a class="btn btn--lg <?= $b['tema'] === 'azul' ? 'btn--naranja' : 'btn--primario' ?>" href="<?= e(enlace($b['url'])) ?>"><?= e($b['texto_boton']) ?></a>
            <?php endif; ?>
          </div>
          <div class="slide__media">
            <img src="<?= e(img($b['imagen'])) ?>" alt="" width="400" height="400"<?= $i > 0 ? ' loading="lazy"' : ' fetchpriority="high"' ?>>
            <?php if ($b['etiqueta_valor']): ?>
              <p class="colgante"><strong><?= e($b['etiqueta_valor']) ?></strong><span><?= e($b['etiqueta_texto']) ?></span></p>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if (count($banners) > 1): ?>
      <div class="hero__controles">
        <button type="button" class="hero__flecha" data-slide-prev><?= icono('izq') ?><span class="sr-only">Promoción anterior</span></button>
        <div class="hero__puntos" role="group" aria-label="Elegir promoción">
          <?php foreach ($banners as $i => $b): ?>
            <button type="button" class="hero__punto" data-slide-dot="<?= $i ?>" aria-label="Ver promoción <?= $i + 1 ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>></button>
          <?php endforeach; ?>
        </div>
        <button type="button" class="hero__flecha" data-slide-next><?= icono('der') ?><span class="sr-only">Promoción siguiente</span></button>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section class="wrap beneficios" aria-label="Beneficios">
  <ul>
    <li><?= icono('tarjeta') ?><p><strong>Hasta 12 cuotas sin interés</strong><span>Con tarjetas de crédito bancarias</span></p></li>
    <?php if ($descTransf > 0): ?>
      <li><?= icono('transferencia') ?><p><strong><?= e(rtrim(rtrim(number_format($descTransf, 1, ',', ''), '0'), ',')) ?>% menos con transferencia</strong><span>O depósito bancario</span></p></li>
    <?php endif; ?>
    <li><?= icono('camion') ?><p><strong>Envío gratis a CABA y GBA</strong><span>En productos seleccionados</span></p></li>
    <li><?= icono('tienda') ?><p><strong>Retiro sin cargo</strong><span>Por nuestro local</span></p></li>
  </ul>
</section>

<?php if ($accesos): ?>
<section class="wrap seccion" aria-labelledby="t-accesos">
  <div class="seccion__head">
    <h2 class="seccion__titulo" id="t-accesos">Comprá por categoría</h2>
  </div>
  <ul class="accesos">
    <?php foreach ($accesos as $c): ?>
      <li>
        <a class="acceso" href="<?= e(url_categoria($c['slug'])) ?>">
          <span class="acceso__img"><img src="<?= e(img($c['imagen'])) ?>" alt="" width="120" height="120" loading="lazy"></span>
          <span class="acceso__nombre"><?= e($c['nombre']) ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<?php if ($destacados): ?>
<section class="wrap seccion" aria-labelledby="t-destacados">
  <div class="seccion__head">
    <h2 class="seccion__titulo" id="t-destacados">Lo más buscado</h2>
    <a class="seccion__link" href="<?= e(url('productos.php')) ?>">Ver todos los productos</a>
  </div>
  <div class="grid">
    <?php foreach ($destacados as $p) { partial('tarjeta-producto', ['p' => $p]); } ?>
  </div>
</section>
<?php endif; ?>

<section class="wrap promos" aria-label="Promociones destacadas">
  <a class="promo promo--naranja" href="<?= e(url('productos.php', ['ofertas' => 1, 'orden' => 'descuento'])) ?>">
    <span class="promo__titulo">Ofertas de la semana</span>
    <span class="promo__texto">Productos con precio rebajado, ordenados por mayor descuento.</span>
    <span class="promo__cta">Ver ofertas</span>
  </a>
  <a class="promo promo--claro" href="<?= e(whatsapp_link('Hola, quiero hacer una consulta sobre un producto.')) ?>" target="_blank" rel="noopener">
    <span class="promo__titulo">¿No sabés cuál elegir?</span>
    <span class="promo__texto">Contanos qué necesitás y te recomendamos el equipo justo para tu casa.</span>
    <span class="promo__cta"><?= icono('whatsapp') ?> Consultar por WhatsApp</span>
  </a>
</section>

<?php foreach ($secciones as $i => $s): ?>
<section class="wrap seccion" aria-labelledby="t-sec-<?= $i ?>">
  <div class="seccion__head">
    <h2 class="seccion__titulo" id="t-sec-<?= $i ?>"><?= e($s['titulo']) ?></h2>
    <a class="seccion__link" href="<?= e(url_categoria($s['cat']['slug'])) ?>">Ver todo en <?= e($s['titulo']) ?></a>
  </div>
  <div class="grid">
    <?php foreach ($s['items'] as $p) { partial('tarjeta-producto', ['p' => $p]); } ?>
  </div>
</section>
<?php endforeach; ?>

<section class="wrap local" aria-labelledby="t-local">
  <picture class="local__media">
    <source srcset="<?= asset('img/marca/local.webp') ?>" type="image/webp">
    <img class="local__foto" src="<?= asset('img/marca/local.jpg') ?>" alt="Frente del local de <?= e(tienda('nombre')) ?>" width="947" height="957" loading="lazy">
  </picture>
  <div class="local__texto">
    <h2 class="seccion__titulo" id="t-local">Vení a conocer el local</h2>
    <p>Mirá los equipos en persona, consultá con nuestros vendedores y retirá tu compra sin cargo.</p>
    <ul class="local__datos">
      <li><?= icono('ubicacion') ?><span><?= e(tienda('direccion')) ?></span></li>
      <li><?= icono('reloj') ?><span><?= e(tienda('horario')) ?></span></li>
    </ul>
    <a class="btn btn--sec" href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode(tienda('nombre') . ', ' . tienda('direccion'))) ?>" target="_blank" rel="noopener"><?= icono('ubicacion') ?> Cómo llegar</a>
  </div>
</section>
