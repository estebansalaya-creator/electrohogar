<?php
/**
 * @var array    $ent
 * @var int|null $id
 * @var array    $fila
 * @var array    $errores
 */
$tieneImagen = (bool) array_filter($ent['campos'], fn($c) => $c['tipo'] === 'imagen');
?>
<div class="encabezado">
  <h1><?= e($titulo) ?></h1>
  <div class="encabezado__acciones">
    <a class="link" href="<?= e(admin_url('lista.php', ['e' => $ent['clave']])) ?>">Volver a <?= e(mb_strtolower($ent['titulo'])) ?></a>
  </div>
</div>

<?php if ($errores): ?>
  <p class="aviso aviso--error" role="alert"><?= e($errores['_'] ?? 'Revisá los datos marcados.') ?></p>
<?php endif; ?>

<form method="post" class="tarjeta form"<?= $tieneImagen ? ' enctype="multipart/form-data"' : '' ?>>
  <?= csrf_field() ?>
  <div class="form__grilla">
  <?php foreach ($ent['campos'] as $n => $c):
      $v = $fila[$n] ?? null;
      $err = $errores[$n] ?? null;
      $idCampo = 'f-' . $n;
      $ancho = in_array($c['tipo'], ['textarea', 'html', 'imagen', 'bool'], true) || ($c['tipo'] === 'lectura' && mb_strlen((string) $v) > 60);
      $desc = trim(($err ? $idCampo . '-err ' : '') . (!empty($c['ayuda']) ? $idCampo . '-ayuda' : ''));
      $attrs = ($err ? ' aria-invalid="true"' : '') . ($desc ? ' aria-describedby="' . $desc . '"' : '') . (!empty($c['requerido']) ? ' required' : '');
  ?>
    <div class="campo<?= $ancho ? ' campo--ancho' : '' ?><?= $err ? ' campo--error' : '' ?>">
      <?php if ($c['tipo'] === 'bool'): ?>
        <label class="check">
          <input type="checkbox" id="<?= $idCampo ?>" name="<?= e($n) ?>" value="1"<?= $v ? ' checked' : '' ?><?= $attrs ?>>
          <span><?= e($c['label']) ?></span>
        </label>
      <?php else: ?>
        <label for="<?= $idCampo ?>"><?= e($c['label']) ?><?= empty($c['requerido']) && $c['tipo'] !== 'lectura' ? ' <span class="opcional">(opcional)</span>' : '' ?></label>
        <?php switch ($c['tipo']):
          case 'lectura': ?>
            <p class="campo__lectura" id="<?= $idCampo ?>"><?php
              if ($v === null || $v === '') { echo '<span class="tenue">—</span>'; }
              elseif (($c['formato'] ?? '') === 'fechahora') { echo e(date('d/m/Y H:i', strtotime((string) $v))); }
              elseif (($c['formato'] ?? '') === 'fecha') { echo e(date('d/m/Y', strtotime((string) $v))); }
              elseif (($c['formato'] ?? '') === 'email') { echo '<a href="mailto:' . e($v) . '">' . e($v) . '</a>'; }
              else { echo nl2br(e($v)); }
            ?></p>
          <?php break; case 'textarea': ?>
            <textarea id="<?= $idCampo ?>" name="<?= e($n) ?>" rows="3"<?= isset($c['max']) ? ' maxlength="' . (int) $c['max'] . '"' : '' ?><?= $attrs ?>><?= e($v) ?></textarea>
          <?php break; case 'html': ?>
            <textarea id="<?= $idCampo ?>" name="<?= e($n) ?>" rows="16" class="codigo" spellcheck="true"<?= $attrs ?>><?= e($v) ?></textarea>
          <?php break; case 'select': ?>
            <select id="<?= $idCampo ?>" name="<?= e($n) ?>"<?= $attrs ?>>
              <?php if (isset($c['nulo'])): ?><option value=""><?= e($c['nulo']) ?></option><?php endif; ?>
              <?php foreach (crud_opciones($c) as $op => $txt): ?>
                <option value="<?= e($op) ?>"<?= (string) $v === (string) $op ? ' selected' : '' ?>><?= e($txt) ?></option>
              <?php endforeach; ?>
            </select>
          <?php break; case 'numero': ?>
            <input id="<?= $idCampo ?>" name="<?= e($n) ?>" type="number" step="1" inputmode="numeric" value="<?= e($v) ?>"<?= isset($c['min']) ? ' min="' . $c['min'] . '"' : '' ?><?= isset($c['max']) ? ' max="' . $c['max'] . '"' : '' ?><?= $attrs ?>>
          <?php break; case 'decimal': ?>
            <input id="<?= $idCampo ?>" name="<?= e($n) ?>" type="text" inputmode="decimal" value="<?= $v === null ? '' : e(rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',')) ?>"<?= $attrs ?>>
          <?php break; case 'fecha': ?>
            <input id="<?= $idCampo ?>" name="<?= e($n) ?>" type="date" value="<?= e($v) ?>"<?= $attrs ?>>
          <?php break; case 'email': ?>
            <input id="<?= $idCampo ?>" name="<?= e($n) ?>" type="email" value="<?= e($v) ?>"<?= $attrs ?>>
          <?php break; case 'imagen': ?>
            <div class="imagen-campo">
              <?php if ($v): ?>
                <img class="imagen-campo__actual" src="<?= e(img($v)) ?>" alt="Imagen actual" width="120" height="120">
              <?php endif; ?>
              <div>
                <input id="<?= $idCampo ?>" name="<?= e($n) ?>" type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-vista-previa<?= $attrs ?>>
                <?php if ($v): ?>
                  <label class="check check--chico"><input type="checkbox" name="<?= e($n) ?>__quitar" value="1"> <span>Quitar la imagen</span></label>
                <?php endif; ?>
              </div>
            </div>
          <?php break; case 'slug': ?>
            <input id="<?= $idCampo ?>" name="<?= e($n) ?>" value="<?= e($v) ?>" data-slug-desde="f-<?= e($c['desde'] ?? '') ?>"<?= isset($c['max']) ? ' maxlength="' . (int) $c['max'] . '"' : '' ?><?= $attrs ?>>
          <?php break; default: ?>
            <input id="<?= $idCampo ?>" name="<?= e($n) ?>" value="<?= e($v) ?>"<?= isset($c['max']) ? ' maxlength="' . (int) $c['max'] . '"' : '' ?><?= !empty($c['mayusculas']) ? ' class="mayusculas"' : '' ?><?= $attrs ?>>
        <?php endswitch; ?>
      <?php endif; ?>
      <?php if ($err): ?><p class="campo__error" id="<?= $idCampo ?>-err"><?= e($err) ?></p><?php endif; ?>
      <?php if (!empty($c['ayuda'])): ?><p class="campo__ayuda" id="<?= $idCampo ?>-ayuda"><?= e($c['ayuda']) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
  <div class="acciones">
    <button class="btn btn--primario" type="submit">Guardar</button>
    <?php if (!$id && $ent['crear']): ?>
      <button class="btn btn--sec" type="submit" name="despues" value="nuevo">Guardar y cargar otra</button>
    <?php endif; ?>
    <a class="link" href="<?= e(admin_url('lista.php', ['e' => $ent['clave']])) ?>">Cancelar</a>
  </div>
</form>

<?php if ($id && $ent['borrar'] !== false): ?>
  <form method="post" action="<?= e(admin_url('borrar.php')) ?>" class="zona-peligro" data-confirmar="¿Borrar este registro? No se puede deshacer.">
    <?= csrf_field() ?>
    <input type="hidden" name="e" value="<?= e($ent['clave']) ?>">
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <button type="submit" class="btn btn--peligro">Borrar</button>
  </form>
<?php endif; ?>
