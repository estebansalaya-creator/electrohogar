<?php
/**
 * @var string|null $error
 * @var string      $email
 * @var string      $volver
 */
?>
<div class="acceso">
  <img class="acceso__logo" src="<?= asset('img/marca/logo-carlos-confort.png') ?>" alt="<?= e(tienda('nombre')) ?>" width="348" height="152">
  <h1 class="acceso__titulo">Panel de administración</h1>
  <?php if ($error): ?><p class="aviso aviso--error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="<?= e(admin_url('login.php')) ?>" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="volver" value="<?= e($volver) ?>">
    <div class="campo">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?= e($email) ?>" required autocomplete="username" autofocus>
    </div>
    <div class="campo">
      <label for="clave">Contraseña</label>
      <input id="clave" name="clave" type="password" required autocomplete="current-password">
    </div>
    <button class="btn btn--primario btn--bloque" type="submit">Ingresar</button>
  </form>
  <p class="acceso__ayuda">¿Te olvidaste la contraseña? Se cambia desde la PC del local con
    <code>php herramientas\crear_admin.php tu@email.com</code></p>
</div>
