<?php
/**
 * Constancia para el cliente de la solicitud de arrepentimiento (Res. SCI 424/2020).
 * @var array $a  fila de arrepentimientos
 */
?>
<h1 style="margin:0 0 8px;font-size:22px;color:#0B2550;">Recibimos tu solicitud de arrepentimiento</h1>
<p style="margin:0 0 14px;">Hola <?= e(explode(' ', $a['nombre'])[0]) ?>, registramos tu pedido para revocar la compra. Este es tu código de trámite:</p>
<p style="margin:0 0 14px;padding:14px;text-align:center;font-size:24px;font-weight:bold;letter-spacing:1px;background:#E3F3EA;color:#147447;border-radius:8px;"><?= e($a['codigo']) ?></p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.7;">
  <tr><td style="color:#556274;width:40%;">Pedido o factura</td><td><?= e($a['nro_pedido']) ?></td></tr>
  <?php if ($a['producto']): ?><tr><td style="color:#556274;">Producto</td><td><?= e($a['producto']) ?></td></tr><?php endif; ?>
  <tr><td style="color:#556274;">Fecha de la solicitud</td><td><?= e(date('d/m/Y H:i', strtotime($a['created_at']))) ?></td></tr>
</table>
<p style="margin:16px 0 0;">Te vamos a escribir para coordinar la devolución. El producto tiene que estar en las mismas condiciones en que lo recibiste.
  La devolución no tiene costo para vos.</p>
<p style="margin:12px 0 0;font-size:13px;color:#556274;">Guardá este correo: es la constancia de tu solicitud.</p>
