<?php
/** @var array $pagina */
// El contenido es HTML cargado por la tienda (tabla paginas). Se muestra tal cual.
$esContacto = $pagina['slug'] === 'contacto';
?>
<div class="wrap wrap--angosto">
  <?php partial('migas', ['migas' => [[$pagina['titulo'], null]]]); ?>
  <article class="pagina">
    <h1 class="pagina__titulo"><?= e($pagina['titulo']) ?></h1>
    <div class="texto"><?= $pagina['contenido'] ?></div>

    <?php if ($esContacto): ?>
      <ul class="contacto">
        <li><?= icono('whatsapp') ?><div><strong>WhatsApp</strong><a href="<?= e(whatsapp_link('Hola, quiero hacer una consulta.')) ?>" target="_blank" rel="noopener">Escribinos</a></div></li>
        <li><?= icono('telefono') ?><div><strong>Teléfono</strong><a href="tel:<?= e(preg_replace('/[^\d+]/', '', tienda('telefono'))) ?>"><?= e(tienda('telefono')) ?></a></div></li>
        <li><?= icono('mail') ?><div><strong>Email</strong><a href="mailto:<?= e(tienda('email')) ?>"><?= e(tienda('email')) ?></a></div></li>
        <li><?= icono('ubicacion') ?><div><strong>Local</strong><span><?= e(tienda('direccion')) ?></span></div></li>
        <li><?= icono('reloj') ?><div><strong>Horario</strong><span><?= e(tienda('horario')) ?></span></div></li>
      </ul>
    <?php endif; ?>
  </article>
</div>
