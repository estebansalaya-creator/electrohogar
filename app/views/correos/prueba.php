<?php
/** @var string $quien */
?>
<h1 style="margin:0 0 8px;font-size:22px;color:#0B2550;">Correo de prueba</h1>
<p style="margin:0 0 8px;">Si estás leyendo esto, la tienda ya puede enviar correos: avisos de pedidos nuevos, constancias de arrepentimiento y confirmaciones a los clientes.</p>
<p style="margin:0;color:#556274;font-size:13px;">Enviado desde el panel por <?= e($quien) ?> el <?= e(date('d/m/Y H:i')) ?>.</p>
