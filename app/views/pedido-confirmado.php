<?php
/**
 * @var array  $p
 * @var array  $items
 * @var bool   $recienCreado
 * @var string $whatsapp
 * @var bool   $conCorreo
 */
$numero = pedido_numero($p);
$pasos = ['nuevo' => 'Recibido', 'confirmado' => 'Confirmado', 'preparando' => 'En preparación', 'enviado' => $p['entrega'] === 'retiro' ? 'Listo para retirar' : 'Enviado', 'entregado' => 'Entregado'];
$claves = array_keys($pasos);
$actual = array_search($p['estado'], $claves, true);
?>
<div class="wrap wrap--angosto">
  <div class="pedido-ok">
    <?php if ($recienCreado): ?>
      <p class="pedido-ok__check"><?= icono('check') ?></p>
      <h1 class="pagina__titulo">¡Listo, <?= e(explode(' ', $p['nombre'])[0]) ?>! Registramos tu pedido</h1>
    <?php else: ?>
      <h1 class="pagina__titulo">Tu pedido</h1>
    <?php endif; ?>
    <p class="pedido-ok__numero">Pedido N° <strong><?= e($numero) ?></strong></p>

    <?php if ($p['estado'] === 'cancelado'): ?>
      <p class="flash flash--error">Este pedido fue cancelado. Si tenés dudas, escribinos.</p>
    <?php else: ?>
      <ol class="estados" aria-label="Estado del pedido">
        <?php foreach ($pasos as $k => $txt): $i = array_search($k, $claves, true); ?>
          <li class="<?= $i < $actual ? 'is-hecho' : ($i === $actual ? 'is-actual' : '') ?>"<?= $i === $actual ? ' aria-current="step"' : '' ?>><?= e($txt) ?></li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>

    <?php if ($p['estado'] === 'nuevo'): ?>
      <div class="pedido-ok__paso">
        <p><strong>Último paso:</strong> mandanos el pedido por WhatsApp así lo vemos al instante. Te confirmamos stock, forma de pago y entrega.</p>
        <a class="btn btn--whatsapp-lleno btn--lg" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener"><?= icono('whatsapp') ?> Enviar pedido por WhatsApp</a>
        <?php if ($conCorreo): ?><p class="campo__ayuda">También te mandamos el detalle a <?= e($p['email']) ?>.</p><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <section class="pagina pedido-detalle" aria-labelledby="t-detalle">
    <h2 class="ficha__subtitulo" id="t-detalle">Detalle</h2>
    <ul class="resumen__items">
      <?php foreach ($items as $it): ?>
        <li>
          <span class="resumen__item-nombre"><?= (int) $it['cantidad'] ?> x <?= e($it['nombre']) ?><?= $it['marca'] ? ' <span class="tenue">(' . e($it['marca']) . ')</span>' : '' ?></span>
          <span class="resumen__item-precio"><?= money($it['subtotal']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <dl class="totales">
      <div><dt>Subtotal</dt><dd><?= money($p['subtotal']) ?></dd></div>
      <?php if ((float) $p['descuento'] > 0): ?><div class="txt-verde"><dt>Cupón <?= e($p['cupon']) ?></dt><dd>-<?= money($p['descuento']) ?></dd></div><?php endif; ?>
      <div><dt><?= $p['entrega'] === 'retiro' ? 'Retiro en el local' : 'Envío' ?></dt><dd><?= $p['entrega'] === 'retiro' ? 'Sin cargo' : ($p['costo_envio'] === null ? 'A cotizar' : ((float) $p['costo_envio'] == 0 ? '<span class="txt-verde">Gratis</span>' : money($p['costo_envio']))) ?></dd></div>
      <div class="totales__total"><dt>Total</dt><dd><?= money($p['total']) ?></dd></div>
      <?php if ((float) $p['total_transferencia'] < (float) $p['total']): ?>
        <div><dt>Con transferencia</dt><dd><?= money($p['total_transferencia']) ?></dd></div>
      <?php endif; ?>
    </dl>
    <p class="pedido-detalle__entrega"><?= e(pedido_texto_entrega($p)) ?></p>
    <p class="campo__ayuda">Guardá este enlace para ver el estado de tu pedido.</p>
  </section>
</div>
