<?php
/**
 * @var array       $cfg
 * @var array       $errores
 * @var array       $historial
 * @var string|null $problemaCifrado
 * @var bool        $tokenIlegible
 * @var bool        $secretoIlegible
 * @var string      $accion
 */
$conectado = $cfg['access_token'] !== '';
$prueba = $conectado && mp_es_prueba($cfg['access_token'], $cfg['cuenta_nombre']);
$err = fn(string $k) => $errores[$k] ?? null;
$errGuardar = $accion === 'guardar' ? $err('clave_actual') : null;
$errBorrar = $accion === 'borrar' ? $err('clave_actual') : null;
?>
<div class="encabezado">
  <h1>Mercado Pago</h1>
</div>

<?php if ($problemaCifrado): ?>
  <section class="tarjeta pendientes" aria-labelledby="t-llave">
    <h2 id="t-llave">Falta configurar la llave de cifrado</h2>
    <p><?= e($problemaCifrado) ?></p>
    <p class="campo__ayuda mt">Las credenciales se guardan cifradas con esa llave, que vive fuera de la base. Hasta que esté, no se pueden guardar.</p>
  </section>
<?php endif; ?>

<?php if ($errores): ?><p class="aviso aviso--error" role="alert">Revisá los datos marcados.</p><?php endif; ?>

<div class="columnas columnas--correo">
  <section class="tarjeta" aria-labelledby="t-estado">
    <h2 id="t-estado">Estado</h2>
    <?php if ($tokenIlegible): ?>
      <p class="aviso aviso--error">Hay un Access Token guardado pero no se puede leer: cambió o falta la llave APP_KEY. Cargalo de nuevo.</p>
    <?php elseif (!$conectado): ?>
      <p><span class="estado estado--no">Sin conectar</span></p>
      <p class="tenue mt">La tienda todavía no puede cobrar con Mercado Pago. Cargá las credenciales de la cuenta del comercio.</p>
    <?php else: ?>
      <dl class="datos">
        <div><dt>Estado</dt><dd><span class="estado estado--si">Conectado</span> <?php if ($prueba): ?><span class="estado estado--en-proceso">Modo prueba</span><?php endif; ?></dd></div>
        <div><dt>Cuenta</dt><dd><?= e($cfg['cuenta_nombre'] ?: '—') ?><?= $cfg['cuenta_id'] ? ' <span class="tenue">(N° ' . e($cfg['cuenta_id']) . ')</span>' : '' ?></dd></div>
        <?php if ($cfg['cuenta_email']): ?><div><dt>Email</dt><dd><?= e($cfg['cuenta_email']) ?></dd></div><?php endif; ?>
        <div><dt>Access Token</dt><dd><code><?= e(enmascarar($cfg['access_token'])) ?></code></dd></div>
        <div><dt>Clave secreta</dt><dd><?= $cfg['webhook_secret'] !== '' ? 'Guardada' : ($secretoIlegible ? '<span class="txt-naranja">No se puede leer: cargala de nuevo</span>' : '<span class="tenue">Sin cargar</span>') ?></dd></div>
        <?php if ($cfg['verificado']): ?><div><dt>Verificado</dt><dd><?= e(date('d/m/Y H:i', strtotime($cfg['verificado']))) ?></dd></div><?php endif; ?>
      </dl>
      <form method="post" class="mt">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="verificar">
        <button class="btn btn--sec btn--chico mt" type="submit">Verificar ahora</button>
      </form>
    <?php endif; ?>
  </section>

  <section class="tarjeta" aria-labelledby="t-donde">
    <h2 id="t-donde">Dónde se consiguen</h2>
    <ol class="pasos">
      <li>Entrá a <strong>mercadopago.com.ar/developers</strong> con la cuenta del comercio (la que recibe la plata).</li>
      <li>En <strong>Tus integraciones</strong>, creá una aplicación (tipo Checkout Pro) o abrí la que ya tengas.</li>
      <li><strong>Credenciales de producción → Access Token</strong>: empieza con <code>APP_USR-</code>.</li>
      <li><strong>Webhooks</strong>: al guardar la configuración aparece la <strong>clave secreta</strong>. La dirección para los avisos te la damos cuando armemos el cobro.</li>
    </ol>
    <p class="campo__ayuda mt">Para probar sin plata real, usá las credenciales de una cuenta de prueba.</p>
  </section>
</div>

<form method="post" class="tarjeta form" aria-labelledby="t-cargar" autocomplete="off">
  <?= csrf_field() ?>
  <input type="hidden" name="accion" value="guardar">
  <h2 id="t-cargar"><?= $conectado ? 'Cambiar credenciales' : 'Cargar credenciales' ?></h2>
  <?php if ($conectado): ?><p class="campo__ayuda">Completá solo lo que querés cambiar. Lo que dejes vacío queda como está.</p><?php endif; ?>
  <div class="form__grilla">
    <div class="campo<?= $err('access_token') ? ' campo--error' : '' ?>">
      <label for="mp-token">Access Token</label>
      <input id="mp-token" name="access_token" type="password" maxlength="220" autocomplete="off" spellcheck="false"
        placeholder="<?= $conectado ? 'Guardado. Pegá uno nuevo para cambiarlo' : 'APP_USR-…' ?>"<?= $err('access_token') ? ' aria-invalid="true" aria-describedby="mp-token-err"' : '' ?><?= $problemaCifrado ? ' disabled' : '' ?>>
      <?php if ($err('access_token')): ?><p class="campo__error" id="mp-token-err"><?= e($err('access_token')) ?></p><?php endif; ?>
      <p class="campo__ayuda">Antes de guardarlo le preguntamos a Mercado Pago de qué cuenta es.</p>
    </div>
    <div class="campo<?= $err('webhook_secret') ? ' campo--error' : '' ?>">
      <label for="mp-secreto">Clave secreta de notificaciones <span class="opcional">(webhooks)</span></label>
      <input id="mp-secreto" name="webhook_secret" type="password" maxlength="200" autocomplete="off" spellcheck="false"
        placeholder="<?= $cfg['webhook_secret'] !== '' ? 'Guardada. Pegá una nueva para cambiarla' : '' ?>"<?= $err('webhook_secret') ? ' aria-invalid="true" aria-describedby="mp-secreto-err"' : '' ?><?= $problemaCifrado ? ' disabled' : '' ?>>
      <?php if ($err('webhook_secret')): ?><p class="campo__error" id="mp-secreto-err"><?= e($err('webhook_secret')) ?></p><?php endif; ?>
      <p class="campo__ayuda">Sirve para comprobar que los avisos de pago vienen de Mercado Pago.</p>
    </div>
    <div class="campo<?= $errGuardar ? ' campo--error' : '' ?>">
      <label for="mp-clave">Tu contraseña del panel</label>
      <input id="mp-clave" name="clave_actual" type="password" required autocomplete="current-password"<?= $errGuardar ? ' aria-invalid="true" aria-describedby="mp-clave-err"' : '' ?><?= $problemaCifrado ? ' disabled' : '' ?>>
      <?php if ($errGuardar): ?><p class="campo__error" id="mp-clave-err"><?= e($errGuardar) ?></p><?php endif; ?>
      <p class="campo__ayuda">Para confirmar que sos vos. Cada cambio queda registrado y se avisa por email.</p>
    </div>
  </div>
  <div class="acciones">
    <button class="btn btn--primario" type="submit"<?= $problemaCifrado ? ' disabled' : '' ?>>Guardar credenciales</button>
  </div>
</form>

<section class="tarjeta" aria-labelledby="t-hist">
  <h2 id="t-hist">Cambios registrados</h2>
  <?php if (!$historial): ?>
    <p class="tenue">Todavía no hubo cambios.</p>
  <?php else: ?>
    <ul class="lista-simple">
      <?php foreach ($historial as $h): ?>
        <li>
          <span class="historial-texto"><?= e($h['detalle'] ?? '') ?></span>
          <span class="tenue"><?= e($h['usuario']) ?> · <?= e(date('d/m/Y H:i', strtotime($h['created_at']))) ?> · <?= e($h['ip'] ?? '') ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php if ($conectado || $cfg['token_guardado']): ?>
  <form method="post" class="tarjeta form zona-peligro" aria-labelledby="t-borrar">
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="borrar">
    <h2 id="t-borrar">Desconectar Mercado Pago</h2>
    <p class="campo__ayuda">Borra el Access Token y la clave secreta. La tienda deja de poder cobrar con Mercado Pago hasta que se carguen de nuevo.</p>
    <div class="campo form--angosto<?= $errBorrar ? ' campo--error' : '' ?>">
      <label for="mp-clave-borrar">Tu contraseña del panel</label>
      <input id="mp-clave-borrar" name="clave_actual" type="password" required autocomplete="current-password"<?= $errBorrar ? ' aria-invalid="true" aria-describedby="mp-borrar-err"' : '' ?>>
      <?php if ($errBorrar): ?><p class="campo__error" id="mp-borrar-err"><?= e($errBorrar) ?></p><?php endif; ?>
    </div>
    <button class="btn btn--peligro" type="submit">Borrar credenciales</button>
  </form>
<?php endif; ?>
