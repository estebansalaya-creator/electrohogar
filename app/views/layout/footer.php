</main>

<section class="beneficios-footer" aria-label="Formas de pago">
  <div class="wrap pagos">
    <p class="pagos__titulo">Medios de pago</p>
    <ul class="pagos__lista">
      <li>Visa</li><li>Mastercard</li><li>American Express</li><li>Cabal</li><li>Naranja X</li>
      <li>Mercado Pago</li><li>Transferencia bancaria</li><li>Efectivo en el local</li>
    </ul>
  </div>
</section>

<footer class="footer">
  <div class="wrap footer__grid">
    <div class="footer__col footer__marca">
      <a class="logo logo--footer" href="<?= e(url('')) ?>">
        <picture>
          <source srcset="<?= asset('img/marca/logo-carlos-confort.webp') ?>" type="image/webp">
          <img src="<?= asset('img/marca/logo-carlos-confort.png') ?>" alt="<?= e(tienda('nombre')) ?>, ir al inicio" width="348" height="152" loading="lazy">
        </picture>
      </a>
      <p class="footer__lema">La mejor elección para su hogar.</p>
      <ul class="footer__contacto">
        <li><?= icono('ubicacion') ?><span><?= e(tienda('direccion')) ?></span></li>
        <li><?= icono('telefono') ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', tienda('telefono'))) ?>"><?= e(tienda('telefono')) ?></a></li>
        <li><?= icono('whatsapp') ?><a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener">Escribinos por WhatsApp</a></li>
        <li><?= icono('mail') ?><a href="mailto:<?= e(tienda('email')) ?>"><?= e(tienda('email')) ?></a></li>
        <li><?= icono('reloj') ?><span><?= e(tienda('horario')) ?></span></li>
      </ul>
    </div>

    <div class="footer__col">
      <p class="footer__titulo">Ayuda</p>
      <ul>
        <li><a href="<?= e(url('pagina.php', ['slug' => 'como-comprar'])) ?>">Cómo comprar</a></li>
        <li><a href="<?= e(url('pagina.php', ['slug' => 'envios'])) ?>">Envíos</a></li>
        <li><a href="<?= e(url('pagina.php', ['slug' => 'cambios-y-devoluciones'])) ?>">Cambios y devoluciones</a></li>
        <li><a href="<?= e(url('pagina.php', ['slug' => 'preguntas-frecuentes'])) ?>">Preguntas frecuentes</a></li>
        <li><a href="<?= e(url('pagina.php', ['slug' => 'contacto'])) ?>">Contacto</a></li>
      </ul>
    </div>

    <div class="footer__col">
      <p class="footer__titulo">La empresa</p>
      <ul>
        <li><a href="<?= e(url('pagina.php', ['slug' => 'quienes-somos'])) ?>">Quiénes somos</a></li>
        <li><a href="<?= e(url('pagina.php', ['slug' => 'terminos-y-condiciones'])) ?>">Términos y condiciones</a></li>
        <li><a href="<?= e(url('pagina.php', ['slug' => 'politica-de-privacidad'])) ?>">Política de privacidad</a></li>
      </ul>
    </div>

    <div class="footer__col footer__news">
      <p class="footer__titulo" id="news-titulo">Ofertas en tu email</p>
      <p class="footer__texto">Te avisamos de promociones y nuevos ingresos. Un email por semana, como mucho.</p>
      <form class="news" action="<?= e(url('api/newsletter.php')) ?>" method="post" data-newsletter aria-labelledby="news-titulo">
        <?= csrf_field() ?>
        <label class="sr-only" for="news-email">Tu email</label>
        <input id="news-email" type="email" name="email" required placeholder="tu@email.com" autocomplete="email">
        <button class="btn btn--naranja" type="submit">Suscribirme</button>
        <p class="news__msg" data-newsletter-msg role="status"></p>
      </form>
    </div>
  </div>

  <div class="wrap footer__legal">
    <a class="btn-arrepentimiento" href="<?= e(url('arrepentimiento.php')) ?>"><?= icono('devolucion') ?> Botón de arrepentimiento</a>
    <a class="footer__defensa" href="https://www.argentina.gob.ar/produccion/defensadelconsumidor/formulario" target="_blank" rel="noopener">
      Defensa de las y los consumidores. Para reclamos ingresá aquí.
    </a>
    <p class="footer__copy">
      © <?= date('Y') ?> <?= e(tienda('razon_social') ?: tienda('nombre')) ?>.
      <?php if (tienda('cuit')): ?>CUIT <?= e(tienda('cuit')) ?>.<?php endif; ?>
      Precios finales con IVA incluido.
    </p>
  </div>
</footer>

<!-- Mini carrito -->
<div class="overlay" data-overlay hidden></div>
<aside class="drawer" id="mini-carrito" aria-labelledby="mini-carrito-titulo" role="dialog" aria-modal="true" hidden data-drawer>
  <div class="drawer__head">
    <p class="drawer__title" id="mini-carrito-titulo">Tu carrito</p>
    <button type="button" class="icon-btn" data-drawer-close><?= icono('cerrar') ?><span class="sr-only">Cerrar carrito</span></button>
  </div>
  <p class="drawer__msg" data-drawer-msg role="status"></p>
  <div class="drawer__body" data-drawer-items></div>
  <div class="drawer__foot" data-drawer-foot>
    <div class="drawer__total"><span>Subtotal</span><strong data-drawer-subtotal></strong></div>
    <p class="drawer__transf">Con transferencia: <strong data-drawer-transf></strong></p>
    <a class="btn btn--primario btn--block" href="<?= e(url('carrito.php')) ?>">Ver carrito</a>
    <button type="button" class="btn btn--link btn--block" data-drawer-close>Seguir comprando</button>
  </div>
</aside>

<?php if (empty($_COOKIE['cc_aviso'])): ?>
<div class="cookies" data-cookies role="region" aria-label="Aviso de cookies">
  <p>Usamos cookies propias para que funcione el carrito. <a href="<?= e(url('pagina.php', ['slug' => 'politica-de-privacidad'])) ?>">Más información</a></p>
  <button type="button" class="btn btn--primario btn--sm" data-cookies-ok>Entendido</button>
</div>
<?php endif; ?>
</body>
</html>
