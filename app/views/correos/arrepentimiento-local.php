<?php
/**
 * Aviso al local de una solicitud de arrepentimiento.
 * @var array $a
 */
?>
<h1 style="margin:0 0 8px;font-size:22px;color:#0B2550;">Nueva solicitud de arrepentimiento</h1>
<p style="margin:0 0 14px;color:#C24A00;font-weight:bold;">Hay que contactar al cliente: tiene derecho a devolver el producto dentro de los 10 días.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.7;">
  <tr><td style="color:#556274;width:40%;">Código</td><td><strong><?= e($a['codigo']) ?></strong></td></tr>
  <tr><td style="color:#556274;">Nombre</td><td><?= e($a['nombre']) ?></td></tr>
  <tr><td style="color:#556274;">DNI / CUIT</td><td><?= e($a['dni']) ?></td></tr>
  <tr><td style="color:#556274;">Email</td><td><a href="mailto:<?= e($a['email']) ?>" style="color:#173B78;"><?= e($a['email']) ?></a></td></tr>
  <?php if ($a['telefono']): ?><tr><td style="color:#556274;">Teléfono</td><td><?= e($a['telefono']) ?></td></tr><?php endif; ?>
  <tr><td style="color:#556274;">Pedido o factura</td><td><?= e($a['nro_pedido']) ?></td></tr>
  <?php if ($a['fecha_compra']): ?><tr><td style="color:#556274;">Fecha de compra</td><td><?= e(date('d/m/Y', strtotime($a['fecha_compra']))) ?></td></tr><?php endif; ?>
  <?php if ($a['producto']): ?><tr><td style="color:#556274;">Producto</td><td><?= e($a['producto']) ?></td></tr><?php endif; ?>
  <?php if ($a['motivo']): ?><tr><td style="color:#556274;vertical-align:top;">Comentarios</td><td><?= nl2br(e($a['motivo'])) ?></td></tr><?php endif; ?>
</table>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 0;">
  <tr><td style="border-radius:6px;background:#0B2550;">
    <a href="<?= e(url_absoluta('admin/editar.php', ['e' => 'arrepentimientos', 'id' => $a['id']])) ?>" style="display:inline-block;padding:12px 20px;color:#FFFFFF;text-decoration:none;font-weight:bold;">Abrir en el panel</a>
  </td></tr>
</table>
