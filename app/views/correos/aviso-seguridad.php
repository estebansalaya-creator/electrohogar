<?php
/**
 * Aviso de un cambio sensible en el panel (credenciales de cobro). Nunca incluye claves.
 * @var string $titulo
 * @var array  $detalle  etiqueta => valor
 * @var string $nota
 */
$td = 'padding:7px 0;border-bottom:1px solid #E6EAF0;vertical-align:top;font-size:14px;';
?>
<h1 style="margin:0 0 8px;font-size:21px;color:#0B2550;">Aviso de seguridad</h1>
<p style="margin:0 0 14px;">En el panel de la tienda <strong><?= e($titulo) ?></strong>.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 14px;">
  <?php foreach ($detalle as $k => $v): ?>
    <tr>
      <td style="<?= $td ?>color:#556274;width:38%;padding-right:10px;"><?= e($k) ?></td>
      <td style="<?= $td ?><?= $k === 'Atención' ? 'color:#C24A00;font-weight:bold;' : '' ?>"><?= e($v) ?></td>
    </tr>
  <?php endforeach; ?>
</table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FFF3EB;border-left:4px solid #FF6600;border-radius:4px;">
  <tr><td style="padding:12px 14px;font-size:14px;line-height:1.5;"><?= e($nota) ?></td></tr>
</table>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:18px 0 0;">
  <tr><td style="border-radius:6px;background:#0B2550;">
    <a href="<?= e(url_absoluta('admin/mercadopago.php')) ?>" style="display:inline-block;padding:12px 20px;color:#FFFFFF;text-decoration:none;font-weight:bold;">Abrir el panel</a>
  </td></tr>
</table>
