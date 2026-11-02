<?php
/**
 * Estructura común de los correos (tablas y estilos en línea para Gmail/Outlook).
 * @var string $contenido
 * @var string $asunto
 */
$logo = url_absoluta('assets/img/marca/logo-carlos-confort.png');
$wa = preg_replace('/\D+/', '', tienda('whatsapp'));
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($asunto) ?></title>
</head>
<body style="margin:0;padding:0;background:#F3F5F8;font-family:Arial,Helvetica,sans-serif;color:#121D2E;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3F5F8;">
  <tr>
    <td align="center" style="padding:24px 12px;">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#FFFFFF;border-radius:10px;overflow:hidden;border:1px solid #D9DFE8;">
        <tr>
          <td style="padding:18px 24px;border-bottom:4px solid #FF6600;">
            <img src="<?= e($logo) ?>" alt="<?= e(tienda('nombre')) ?>" width="150" style="display:block;width:150px;height:auto;border:0;">
          </td>
        </tr>
        <tr>
          <td style="padding:24px;font-size:15px;line-height:1.55;">
            <?= $contenido ?>
          </td>
        </tr>
        <tr>
          <td style="padding:16px 24px;background:#0B2550;color:#D6DEEB;font-size:13px;line-height:1.6;">
            <strong style="color:#FFFFFF;"><?= e(tienda('nombre')) ?></strong> · La mejor elección para su hogar<br>
            <?= e(tienda('direccion')) ?><br>
            Tel. <?= e(tienda('telefono')) ?><?php if (strlen($wa) >= 10): ?> · WhatsApp <a href="https://wa.me/<?= e($wa) ?>" style="color:#FF6600;">+<?= e($wa) ?></a><?php endif; ?><br>
            <a href="<?= e(url_absoluta('')) ?>" style="color:#FFFFFF;"><?= e(preg_replace('#^https?://#', '', url_absoluta(''))) ?></a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
