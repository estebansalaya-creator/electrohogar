<?php
/**
 * Aviso al cliente de que su pedido cambió de estado.
 * @var array  $p
 * @var array  $items
 * @var string $numero
 * @var string $mensaje   texto opcional que escribió el local
 */
$nombre = e(explode(' ', $p['nombre'])[0]);
$textos = [
    'confirmado' => 'Confirmamos tu pedido. Ya separamos los productos para vos.',
    'preparando' => 'Estamos preparando tu pedido.',
    'enviado'    => $p['entrega'] === 'retiro'
        ? 'Tu pedido ya está listo para retirar en el local. Traé tu DNI y el número de pedido.'
        : 'Tu pedido ya salió para tu domicilio.',
    'entregado'  => 'Tu pedido figura como entregado. ¡Gracias por elegirnos!',
    'cancelado'  => 'Tu pedido fue cancelado. Si no lo esperabas, respondé este email o escribinos por WhatsApp.',
    'nuevo'      => 'Tu pedido volvió a estar pendiente de confirmación. Te vamos a contactar.',
];
$urlPedido = url_absoluta('pedido.php', ['t' => $p['token']]);
?>
<h1 style="margin:0 0 8px;font-size:22px;color:#0B2550;">Hola, <?= $nombre ?></h1>
<p style="margin:0 0 6px;">Tu pedido <strong>N° <?= e($numero) ?></strong> está en estado <strong><?= e(pedido_estado_nombre($p)) ?></strong>.</p>
<p style="margin:0 0 6px;"><?= e($textos[$p['estado']] ?? '') ?></p>
<?php if (!empty($mensaje)): ?>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:14px 0;background:#F6F8FA;border-left:4px solid #FF6600;border-radius:4px;">
    <tr><td style="padding:12px 14px;font-size:15px;line-height:1.6;"><?= nl2br(e($mensaje)) ?></td></tr>
  </table>
<?php endif; ?>
<?php require __DIR__ . '/_pedido.php'; ?>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 4px;">
  <tr><td style="border-radius:6px;background:#0B2550;">
    <a href="<?= e($urlPedido) ?>" style="display:inline-block;padding:12px 20px;color:#FFFFFF;text-decoration:none;font-weight:bold;">Ver mi pedido</a>
  </td></tr>
</table>
