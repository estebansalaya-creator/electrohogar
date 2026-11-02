<?php
/**
 * @var array  $valores
 * @var array  $errores
 * @var bool   $hayClave
 * @var bool   $claveIlegible
 * @var bool   $configurado
 * @var array  $items
 * @var int    $total
 * @var int    $pagina
 * @var int    $paginas
 * @var string $estado
 * @var array  $estados
 * @var array  $conteo
 * @var array  $yo
 */
$tipos = [
    'pedido_local'           => 'Pedido nuevo (al local)',
    'pedido_cliente'         => 'Pedido recibido (al cliente)',
    'pedido_estado'          => 'Cambio de estado (al cliente)',
    'arrepentimiento_local'  => 'Arrepentimiento (al local)',
    'arrepentimiento_cliente'=> 'Constancia de arrepentimiento (al cliente)',
    'prueba'                 => 'Prueba',
    'seguridad'              => 'Aviso de seguridad',
];
$nombresEstado = ['pendiente' => 'Pendiente', 'enviado' => 'Enviado', 'error' => 'Con error', 'sin_configurar' => 'Sin enviar'];
$campo = function (string $k, string $label, string $tipo = 'text', ?string $ayuda = null, string $extra = '') use ($valores, $errores): void {
    $err = $errores[$k] ?? null; ?>
    <div class="campo<?= $err ? ' campo--error' : '' ?>">
      <label for="c-<?= e($k) ?>"><?= e($label) ?></label>
      <input id="c-<?= e($k) ?>" name="<?= e($k) ?>" type="<?= e($tipo) ?>" value="<?= e($valores[$k]) ?>" maxlength="190"<?= $extra ?><?= $err ? ' aria-invalid="true" aria-describedby="c-' . e($k) . '-err"' : '' ?>>
      <?php if ($err): ?><p class="campo__error" id="c-<?= e($k) ?>-err"><?= e($err) ?></p><?php endif; ?>
      <?php if ($ayuda): ?><p class="campo__ayuda"><?= e($ayuda) ?></p><?php endif; ?>
    </div>
<?php };
$pendientes = (int) ($conteo['pendiente'] ?? 0) + (int) ($conteo['error'] ?? 0);
?>
<div class="encabezado">
  <h1>Correos</h1>
</div>

<?php if (!$configurado): ?>
  <section class="tarjeta pendientes" aria-labelledby="t-modo">
    <h2 id="t-modo">Modo prueba: los correos no salen</h2>
    <p>Todavía no hay una casilla configurada. Los avisos de pedidos y arrepentimientos se guardan igual en la lista de abajo,
      así podés ver cómo quedan. Cuando tengas los datos de la casilla (los da el proveedor del dominio o del hosting), completalos acá.</p>
  </section>
<?php endif; ?>

<?php if ($errores): ?><p class="aviso aviso--error" role="alert">Revisá los datos marcados.</p><?php endif; ?>

<div class="columnas columnas--correo">
  <form method="post" class="tarjeta form" aria-labelledby="t-casilla">
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="config">
    <h2 id="t-casilla">Casilla de envío</h2>
    <div class="form__grilla">
      <?php $campo('host', 'Servidor de salida (SMTP)', 'text', 'Ej: smtp.gmail.com, mail.carlosconfort.com.ar', ' autocomplete="off" spellcheck="false"'); ?>
      <div class="campo<?= isset($errores['seguridad']) ? ' campo--error' : '' ?>">
        <label for="c-seguridad">Seguridad</label>
        <select id="c-seguridad" name="seguridad">
          <?php foreach (['ssl' => 'SSL (puerto 465)', 'tls' => 'TLS / STARTTLS (puerto 587)', 'ninguna' => 'Ninguna (no recomendado)'] as $k => $t): ?>
            <option value="<?= e($k) ?>"<?= $valores['seguridad'] === $k ? ' selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php $campo('puerto', 'Puerto', 'text', 'Vacío: 465 con SSL, 587 con TLS.', ' inputmode="numeric" maxlength="5"'); ?>
      <?php $campo('usuario', 'Usuario', 'text', 'Casi siempre es la dirección de email completa.', ' autocomplete="off" spellcheck="false"'); ?>
      <div class="campo">
        <label for="c-clave">Contraseña</label>
        <input id="c-clave" name="clave" type="password" value="" autocomplete="new-password" maxlength="190"
          placeholder="<?= $hayClave ? 'Guardada. Escribí una nueva para cambiarla' : '' ?>">
        <?php if ($hayClave): ?>
          <label class="check check--chico"><input type="checkbox" name="borrar_clave" value="1"> Borrar la contraseña guardada</label>
        <?php endif; ?>
        <?php if ($claveIlegible): ?>
          <p class="campo__error">La contraseña guardada no se puede leer (cambió la llave APP_KEY). Escribila de nuevo.</p>
        <?php endif; ?>
        <p class="campo__ayuda">Con Gmail hay que usar una "contraseña de aplicación", no la clave normal.<?= cifrado_disponible() ? ' Se guarda cifrada.' : '' ?></p>
      </div>
      <?php $campo('remitente', 'Email remitente', 'email', 'Desde dónde salen los correos. Vacío: el usuario.'); ?>
      <?php $campo('remitente_nombre', 'Nombre del remitente', 'text', 'Vacío: ' . tienda('nombre') . '.'); ?>
      <?php $campo('avisos', 'Email que recibe los avisos', 'email', 'Pedidos nuevos y arrepentimientos. Vacío: ' . (tienda('email') ?: 'el email de la tienda') . '.'); ?>
    </div>
    <label class="check check--chico"><input type="checkbox" name="verificar_ssl" value="1"<?= $valores['verificar_ssl'] === '1' ? ' checked' : '' ?>>
      Verificar el certificado del servidor (destildalo solo si el proveedor usa un certificado propio)</label>
    <p class="campo__ayuda">También se pueden poner en <code>config.local.php</code> con <code>MAIL_HOST</code>, <code>MAIL_USER</code>, <code>MAIL_PASS</code>, etc. Lo que guardes acá tiene prioridad.</p>
    <div class="acciones">
      <button class="btn btn--primario" type="submit">Guardar casilla</button>
    </div>
  </form>

  <section class="tarjeta" aria-labelledby="t-prueba">
    <h2 id="t-prueba">Probar el envío</h2>
    <?php if ($configurado): ?>
      <form method="post" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="probar">
        <div class="campo">
          <label for="p-para">Mandar un correo de prueba a</label>
          <input id="p-para" name="para" type="email" value="<?= e($yo['email'] ?? '') ?>" required maxlength="190">
        </div>
        <button class="btn btn--sec" type="submit">Enviar prueba</button>
      </form>
      <?php if ($pendientes): ?>
        <form method="post" class="mt">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="procesar">
          <p class="campo__ayuda">Hay <?= $pendientes ?> <?= plural($pendientes, 'correo pendiente o con error', 'correos pendientes o con error') ?>.</p>
          <button class="btn btn--sec btn--chico mt" type="submit">Reintentar pendientes</button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <p class="tenue">Cuando guardes los datos de la casilla vas a poder mandarte un correo de prueba desde acá.</p>
    <?php endif; ?>
  </section>
</div>

<div class="encabezado mt-grande">
  <h2 class="encabezado__sub">Correos generados <span class="encabezado__cant"><?= $total ?></span></h2>
</div>
<form class="filtros" method="get">
  <label class="sr-only" for="f-estado">Estado</label>
  <select id="f-estado" name="estado" data-autosubmit>
    <option value="">Todos</option>
    <?php foreach ($estados as $k => $t): ?>
      <option value="<?= e($k) ?>"<?= $estado === $k ? ' selected' : '' ?>><?= e($t) ?> (<?= (int) ($conteo[$k] ?? 0) ?>)</option>
    <?php endforeach; ?>
  </select>
  <noscript><button class="btn btn--sec" type="submit">Filtrar</button></noscript>
</form>

<?php if (!$items): ?>
  <div class="tarjeta vacio"><p><?= $estado ? 'No hay correos en ese estado.' : 'Todavía no se generó ningún correo. Aparecen cuando entra un pedido o un arrepentimiento.' ?></p></div>
<?php else: ?>
  <div class="tabla-contenedor tarjeta">
    <table class="tabla tabla--correos">
      <thead>
        <tr><th scope="col">Asunto</th><th scope="col">Para</th><th scope="col" class="col-fecha">Fecha</th><th scope="col">Estado</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $c): ?>
          <tr>
            <td>
              <a class="tabla__principal" href="<?= e(admin_url('correo.php', ['id' => $c['id']])) ?>"><?= e($c['asunto']) ?></a>
              <span class="tabla__sub"><?= e($tipos[$c['tipo']] ?? $c['tipo']) ?></span>
            </td>
            <td><?= e($c['para']) ?></td>
            <td class="col-fecha"><?= e(date('d/m H:i', strtotime($c['created_at']))) ?></td>
            <td>
              <span class="estado estado--correo-<?= e($c['estado']) ?>"><?= e($nombresEstado[$c['estado']] ?? $c['estado']) ?></span>
              <?php if ($c['estado'] === 'error' && $c['error']): ?><span class="tabla__sub" title="<?= e($c['error']) ?>"><?= e(mb_strimwidth($c['error'], 0, 70, '…')) ?></span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($paginas > 1): ?>
    <nav class="paginas" aria-label="Páginas">
      <?php for ($n = 1; $n <= $paginas; $n++): ?>
        <?php if ($n === $pagina): ?><span aria-current="page"><?= $n ?></span>
        <?php else: ?><a href="<?= e(admin_url('correos.php', array_filter(['estado' => $estado]) + ['pagina' => $n])) ?>"><?= $n ?></a><?php endif; ?>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
