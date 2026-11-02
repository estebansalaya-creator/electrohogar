<?php
/**
 * @var array      $c
 * @var int|null   $pedidoId
 * @var int|null   $arrepId
 * @var bool       $configurado
 */
$nombresEstado = ['pendiente' => 'Pendiente', 'enviado' => 'Enviado', 'error' => 'Con error', 'sin_configurar' => 'Sin enviar'];
?>
<div class="encabezado">
  <h1><?= e($c['asunto']) ?></h1>
  <div class="encabezado__acciones">
    <?php if ($pedidoId): ?><a class="btn btn--sec" href="<?= e(admin_url('pedido.php', ['id' => $pedidoId])) ?>">Ver el pedido</a><?php endif; ?>
    <?php if ($arrepId): ?><a class="btn btn--sec" href="<?= e(admin_url('editar.php', ['e' => 'arrepentimientos', 'id' => $arrepId])) ?>">Ver la solicitud</a><?php endif; ?>
    <a class="link" href="<?= e(admin_url('correos.php')) ?>">Volver a correos</a>
  </div>
</div>

<div class="producto-form">
  <section class="tarjeta correo-vista" aria-label="Cómo se ve el correo">
    <iframe src="<?= e(admin_url('correo-html.php', ['id' => $c['id']])) ?>" title="Vista del correo" sandbox="allow-popups allow-popups-to-escape-sandbox" loading="lazy"></iframe>
    <details class="mt">
      <summary>Ver versión de solo texto</summary>
      <pre class="correo-texto"><?= e($c['texto']) ?></pre>
    </details>
  </section>

  <div class="producto-form__lateral pedido-lateral">
    <section class="tarjeta" aria-labelledby="t-datos">
      <h2 id="t-datos">Datos</h2>
      <dl class="datos">
        <div><dt>Para</dt><dd><?= e($c['para']) ?></dd></div>
        <div><dt>Estado</dt><dd><span class="estado estado--correo-<?= e($c['estado']) ?>"><?= e($nombresEstado[$c['estado']] ?? $c['estado']) ?></span></dd></div>
        <div><dt>Generado</dt><dd><?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></dd></div>
        <?php if ($c['enviado_at']): ?><div><dt>Enviado</dt><dd><?= e(date('d/m/Y H:i', strtotime($c['enviado_at']))) ?></dd></div><?php endif; ?>
        <?php if ((int) $c['intentos']): ?><div><dt>Intentos</dt><dd><?= (int) $c['intentos'] ?></dd></div><?php endif; ?>
      </dl>
      <?php if ($c['error'] && $c['estado'] !== 'enviado'): ?>
        <p class="campo__ayuda mt">Último error:</p>
        <p class="campo__lectura correo-error"><?= e($c['error']) ?></p>
      <?php endif; ?>
      <?php if ($c['estado'] !== 'enviado'): ?>
        <?php if ($configurado): ?>
          <form method="post" action="<?= e(admin_url('correos.php')) ?>" class="mt">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="reenviar">
            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <input type="hidden" name="volver" value="correo">
            <button class="btn btn--primario btn--bloque" type="submit">Enviar ahora</button>
          </form>
        <?php else: ?>
          <p class="campo__ayuda mt">Se va a poder enviar cuando configures la casilla en <a href="<?= e(admin_url('correos.php')) ?>">Correos</a>.</p>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>
</div>
