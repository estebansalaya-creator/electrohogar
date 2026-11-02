<?php
/**
 * Aviso al local de un pedido nuevo.
 * @var array  $p
 * @var array  $items
 * @var string $numero
 */
$tel = telefono_whatsapp($p['telefono']);
$waCliente = $tel ? 'https://wa.me/' . $tel : null;
?>
<h1 style="margin:0 0 8px;font-size:22px;color:#0B2550;">Pedido nuevo N° <?= e($numero) ?></h1>
<p style="margin:0 0 12px;color:#556274;font-size:14px;"><?= e(date('d/m/Y H:i', strtotime($p['created_at']))) ?> · desde la tienda online</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;background:#F6F8FA;border-radius:8px;">
  <tr><td style="padding:12px 14px;line-height:1.7;">
    <strong><?= e($p['nombre']) ?></strong><br>
    Teléfono: <?= e($p['telefono']) ?><?php if ($waCliente): ?> · <a href="<?= e($waCliente) ?>" style="color:#147447;">escribirle por WhatsApp</a><?php endif; ?><br>
    <?php if ($p['email']): ?>Email: <a href="mailto:<?= e($p['email']) ?>" style="color:#173B78;"><?= e($p['email']) ?></a><br><?php endif; ?>
    <?php if ($p['comentarios']): ?>Comentarios: <?= nl2br(e($p['comentarios'])) ?><?php endif; ?>
  </td></tr>
</table>
<?php require __DIR__ . '/_pedido.php'; ?>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 0;">
  <tr><td style="border-radius:6px;background:#0B2550;">
    <a href="<?= e(url_absoluta('admin/pedido.php', ['id' => $p['id']])) ?>" style="display:inline-block;padding:12px 20px;color:#FFFFFF;text-decoration:none;font-weight:bold;">Abrir en el panel</a>
  </td></tr>
</table>
