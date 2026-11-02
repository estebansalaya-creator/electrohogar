<?php
/**
 * @var array  $p
 * @var array  $items
 * @var string $numero
 */
$wa = preg_replace('/\D+/', '', tienda('whatsapp'));
$urlPedido = url_absoluta('pedido.php', ['t' => $p['token']]);
?>
<h1 style="margin:0 0 8px;font-size:22px;color:#0B2550;">¡Gracias, <?= e(explode(' ', $p['nombre'])[0]) ?>!</h1>
<p style="margin:0 0 6px;">Recibimos tu pedido <strong>N° <?= e($numero) ?></strong>. Te vamos a contactar para confirmar el stock, la forma de pago y la entrega.</p>
<p style="margin:0 0 6px;color:#556274;font-size:14px;">Si todavía no nos lo mandaste por WhatsApp, podés hacerlo desde el enlace de abajo así lo vemos más rápido.</p>
<?php require __DIR__ . '/_pedido.php'; ?>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 4px;">
  <tr>
    <td style="border-radius:6px;background:#0B2550;">
      <a href="<?= e($urlPedido) ?>" style="display:inline-block;padding:12px 20px;color:#FFFFFF;text-decoration:none;font-weight:bold;">Ver mi pedido</a>
    </td>
    <?php if (strlen($wa) >= 10): ?>
    <td style="width:10px;"></td>
    <td style="border-radius:6px;background:#147447;">
      <a href="<?= e(whatsapp_link(pedido_texto_whatsapp($p, $items))) ?>" style="display:inline-block;padding:12px 20px;color:#FFFFFF;text-decoration:none;font-weight:bold;">Enviar por WhatsApp</a>
    </td>
    <?php endif; ?>
  </tr>
</table>
<p style="margin:16px 0 0;font-size:12px;color:#556274;">Los precios incluyen IVA. Tenés 10 días corridos desde que recibís el producto para arrepentirte de la compra
  (<a href="<?= e(url_absoluta('arrepentimiento.php')) ?>" style="color:#173B78;">Botón de arrepentimiento</a>).</p>
