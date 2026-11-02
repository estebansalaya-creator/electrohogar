<?php
/**
 * @var array|null  $resultado
 * @var string|null $archivoToken
 * @var string|null $error
 */
?>
<div class="encabezado"><h1>Importar productos</h1></div>

<?php if ($error): ?><p class="aviso aviso--error" role="alert"><?= e($error) ?></p><?php endif; ?>

<?php if ($resultado): ?>
  <section class="tarjeta resultado" aria-labelledby="t-res">
    <h2 id="t-res"><?= $resultado['simulado'] ? 'Revisión: esto es lo que va a pasar' : 'Importación terminada' ?></h2>
    <ul class="cifras cifras--chicas">
      <li><span class="cifra"><strong><?= $resultado['insertados'] ?></strong><span>productos nuevos</span></span></li>
      <li><span class="cifra"><strong><?= $resultado['actualizados'] ?></strong><span>productos actualizados</span></span></li>
      <li><span class="cifra<?= $resultado['errores'] ? ' cifra--urgente' : '' ?>"><strong><?= count($resultado['errores']) ?></strong><span>filas con error</span></span></li>
    </ul>
    <?php if ($resultado['categorias']): ?><p>Categorías nuevas: <?= e(implode(', ', $resultado['categorias'])) ?></p><?php endif; ?>
    <?php if ($resultado['marcas']): ?><p>Marcas nuevas: <?= e(implode(', ', $resultado['marcas'])) ?></p><?php endif; ?>
    <?php if ($resultado['errores']): ?>
      <details class="errores-import"<?= count($resultado['errores']) <= 10 ? ' open' : '' ?>>
        <summary>Ver las filas con error</summary>
        <ul><?php foreach ($resultado['errores'] as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
      </details>
    <?php endif; ?>
    <?php if ($resultado['simulado'] && $archivoToken): ?>
      <form method="post" class="acciones">
        <?= csrf_field() ?>
        <input type="hidden" name="paso" value="confirmar">
        <input type="hidden" name="token" value="<?= e($archivoToken) ?>">
        <button class="btn btn--primario" type="submit">Confirmar e importar</button>
        <a class="link" href="<?= e(admin_url('importar.php')) ?>">Cancelar</a>
      </form>
      <p class="campo__ayuda">Las filas con error no se importan; las demás sí.</p>
    <?php else: ?>
      <a class="btn btn--sec" href="<?= e(admin_url('productos.php')) ?>">Ver productos</a>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php if (!$resultado || !$resultado['simulado']): ?>
<form method="post" enctype="multipart/form-data" class="tarjeta form form--angosto">
  <?= csrf_field() ?>
  <input type="hidden" name="paso" value="revisar">
  <div class="campo">
    <label for="archivo">Archivo CSV</label>
    <input id="archivo" name="archivo" type="file" accept=".csv,text/csv" required>
    <p class="campo__ayuda">Primero se revisa sin guardar nada; después confirmás.</p>
  </div>
  <button class="btn btn--primario" type="submit">Revisar archivo</button>
</form>

<section class="tarjeta ayuda-import" aria-labelledby="t-formato">
  <h2 id="t-formato">Cómo armar el archivo</h2>
  <ol>
    <li>Descargá la <a href="<?= e(admin_url('plantilla.php')) ?>">plantilla de ejemplo</a> y abrila en Excel.</li>
    <li>Una fila por producto. Obligatorias: <strong>sku</strong>, <strong>nombre</strong>, <strong>categoria</strong>, <strong>precio</strong>.
      Opcionales: rubro, marca, precio_lista, stock, cuotas, envio_gratis, destacado, imagen, descripcion, iva, activo.</li>
    <li>Guardalo como <em>CSV (delimitado por comas)</em> y subilo acá.</li>
  </ol>
  <p>Si el código (sku) ya existe, el producto se actualiza: sirve para cambiar precios y stock de muchos productos a la vez.
    Las celdas vacías no borran datos. Las categorías y marcas que no existan se crean solas.</p>
</section>
<?php endif; ?>
