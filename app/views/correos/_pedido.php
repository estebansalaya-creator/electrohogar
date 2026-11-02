<?php
/**
 * Detalle del pedido (ítems, totales y entrega) para los correos.
 * @var array $p
 * @var array $items
 */
$td = 'padding:8px 0;border-bottom:1px solid #E6EAF0;vertical-align:top;';
?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin:12px 0;">
  <?php foreach ($items as $it): ?>
    <tr>
      <td style="<?= $td ?>"><?= (int) $it['cantidad'] ?> x <?= e($it['nombre']) ?><?= $it['marca'] ? ' <span style="color:#556274;">(' . e($it['marca']) . ')</span>' : '' ?>
        <br><span style="color:#556274;font-size:12px;">Código <?= e($it['sku']) ?> · <?= money($it['precio_unitario']) ?> c/u</span></td>
      <td align="right" style="<?= $td ?>white-space:nowrap;"><?= money($it['subtotal']) ?></td>
    </tr>
  <?php endforeach; ?>
  <tr><td style="padding:8px 0 2px;">Subtotal</td><td align="right" style="padding:8px 0 2px;"><?= money($p['subtotal']) ?></td></tr>
  <?php if ((float) $p['descuento'] > 0): ?>
    <tr><td style="padding:2px 0;color:#147447;">Cupón <?= e($p['cupon']) ?></td><td align="right" style="padding:2px 0;color:#147447;">-<?= money($p['descuento']) ?></td></tr>
  <?php endif; ?>
  <tr>
    <td style="padding:2px 0;"><?= $p['entrega'] === 'retiro' ? 'Retiro en el local' : 'Envío' . ($p['zona'] ? ' (' . e($p['zona']) . ')' : '') ?></td>
    <td align="right" style="padding:2px 0;"><?= $p['entrega'] === 'retiro' ? 'Sin cargo' : ($p['costo_envio'] === null ? 'A cotizar' : ((float) $p['costo_envio'] == 0 ? 'Gratis' : money($p['costo_envio']))) ?></td>
  </tr>
  <tr>
    <td style="padding:10px 0 2px;font-size:17px;font-weight:bold;border-top:2px solid #0B2550;">Total</td>
    <td align="right" style="padding:10px 0 2px;font-size:17px;font-weight:bold;border-top:2px solid #0B2550;white-space:nowrap;"><?= money($p['total']) ?></td>
  </tr>
  <?php if ((float) $p['total_transferencia'] < (float) $p['total']): ?>
    <tr><td style="padding:2px 0;color:#556274;">Pagando con transferencia</td><td align="right" style="padding:2px 0;color:#556274;white-space:nowrap;"><?= money($p['total_transferencia']) ?></td></tr>
  <?php endif; ?>
  <?php if ((int) $p['cuotas'] > 1): ?>
    <tr><td colspan="2" style="padding:2px 0;color:#147447;font-size:13px;">O hasta <?= (int) $p['cuotas'] ?> cuotas sin interés de <?= money_cuota((float) $p['total'] / (int) $p['cuotas']) ?>.</td></tr>
  <?php endif; ?>
</table>
<p style="margin:12px 0 0;font-size:14px;"><strong>Entrega:</strong> <?= e(pedido_texto_entrega($p)) ?></p>
