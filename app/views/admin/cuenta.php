<?php
/**
 * @var array  $yo
 * @var array  $errores
 * @var string $nombre
 */
?>
<div class="encabezado"><h1>Mi cuenta</h1></div>
<?php foreach ($errores as $err): ?><p class="aviso aviso--error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<form method="post" class="tarjeta form form--angosto">
  <?= csrf_field() ?>
  <div class="campo">
    <label>Email</label>
    <p class="campo__lectura"><?= e($yo['email']) ?></p>
  </div>
  <div class="campo">
    <label for="nombre">Nombre</label>
    <input id="nombre" name="nombre" value="<?= e($nombre) ?>" required maxlength="80">
  </div>
  <fieldset class="grupo">
    <legend>Cambiar la contraseña</legend>
    <p class="campo__ayuda">Dejá estos campos vacíos si no la querés cambiar.</p>
    <div class="campo">
      <label for="nueva">Contraseña nueva</label>
      <input id="nueva" name="nueva" type="password" minlength="10" autocomplete="new-password">
    </div>
    <div class="campo">
      <label for="nueva2">Repetí la contraseña nueva</label>
      <input id="nueva2" name="nueva2" type="password" minlength="10" autocomplete="new-password">
    </div>
  </fieldset>
  <div class="campo">
    <label for="actual">Contraseña actual (para confirmar los cambios)</label>
    <input id="actual" name="actual" type="password" required autocomplete="current-password">
  </div>
  <div class="acciones">
    <button class="btn btn--primario" type="submit">Guardar cambios</button>
  </div>
</form>
