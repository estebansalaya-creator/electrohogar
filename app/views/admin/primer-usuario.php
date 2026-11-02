<?php
/**
 * @var bool  $permitido
 * @var array $errores
 * @var array $datos
 */
?>
<div class="acceso">
  <img class="acceso__logo" src="<?= asset('img/marca/logo-carlos-confort.png') ?>" alt="<?= e(tienda('nombre')) ?>" width="348" height="152">
  <h1 class="acceso__titulo">Crear el primer usuario</h1>
  <?php if (!$permitido): ?>
    <p class="aviso aviso--aviso">Por seguridad, el primer usuario se crea desde la PC donde está instalado el sitio,
      entrando a <strong>http://localhost:8050/admin</strong>, o con el comando
      <code>php herramientas\crear_admin.php tu@email.com "Tu nombre"</code>.</p>
  <?php else: ?>
    <p class="acceso__texto">Todavía no hay usuarios en el panel. El que crees ahora va a poder administrar todo.</p>
    <?php foreach ($errores as $err): ?><p class="aviso aviso--error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
    <form method="post" action="<?= e(admin_url('primer-usuario.php')) ?>" class="form">
      <?= csrf_field() ?>
      <div class="campo">
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" value="<?= e($datos['nombre']) ?>" required maxlength="80" autocomplete="name">
      </div>
      <div class="campo">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= e($datos['email']) ?>" required autocomplete="username">
      </div>
      <div class="campo">
        <label for="clave">Contraseña</label>
        <input id="clave" name="clave" type="password" required minlength="10" autocomplete="new-password">
        <p class="campo__ayuda">Al menos 10 caracteres.</p>
      </div>
      <div class="campo">
        <label for="clave2">Repetí la contraseña</label>
        <input id="clave2" name="clave2" type="password" required minlength="10" autocomplete="new-password">
      </div>
      <button class="btn btn--primario btn--bloque" type="submit">Crear usuario e ingresar</button>
    </form>
  <?php endif; ?>
</div>
