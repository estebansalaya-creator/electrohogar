<?php
/**
 * @var array $campos   clave => [label, tipo, max, ayuda]
 * @var array $valores
 * @var array $errores
 */
$whatsapp = preg_replace('/\D+/', '', $valores['tienda.whatsapp'] ?? '');
?>
<div class="encabezado">
  <h1>Datos de la tienda</h1>
</div>
<?php if ($errores): ?><p class="aviso aviso--error" role="alert">Revisá los datos marcados.</p><?php endif; ?>

<form method="post" class="tarjeta form">
  <?= csrf_field() ?>
  <p class="campo__ayuda">Se muestran en el encabezado, el pie, la página de contacto y los pedidos por WhatsApp.
    Si dejás un campo vacío, se usa el valor de <code>app\config.php</code>.</p>
  <div class="form__grilla">
    <?php foreach ($campos as $clave => [$label, $tipo, $max, $ayuda]):
        $n = str_replace('.', '__', $clave);
        $err = $errores[$clave] ?? null;
        $tipoInput = match ($tipo) { 'email' => 'email', 'url' => 'url', 'whatsapp', 'entero', 'porcentaje', 'cuit' => 'text', default => 'text' };
        $modo = in_array($tipo, ['whatsapp', 'entero', 'cuit'], true) ? ' inputmode="numeric"' : ($tipo === 'porcentaje' ? ' inputmode="decimal"' : '');
    ?>
      <div class="campo<?= $err ? ' campo--error' : '' ?>">
        <label for="f-<?= e($n) ?>"><?= e($label) ?></label>
        <input id="f-<?= e($n) ?>" name="<?= e($n) ?>" type="<?= $tipoInput ?>" value="<?= e($valores[$clave]) ?>" maxlength="<?= (int) $max ?>"<?= $modo ?><?= $err ? ' aria-invalid="true" aria-describedby="f-' . e($n) . '-err"' : '' ?>>
        <?php if ($err): ?><p class="campo__error" id="f-<?= e($n) ?>-err"><?= e($err) ?></p><?php endif; ?>
        <?php if ($ayuda): ?><p class="campo__ayuda"><?= e($ayuda) ?></p><?php endif; ?>
        <?php if ($tipo === 'whatsapp' && strlen($whatsapp) >= 10): ?>
          <a class="link campo__ayuda" href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener">Probar este número en WhatsApp</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="acciones">
    <button class="btn btn--primario" type="submit">Guardar datos</button>
  </div>
</form>
