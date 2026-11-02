<?php
/**
 * @var array    $p
 * @var int|null $id
 * @var array    $errores
 * @var array    $fotos   [['id', 'ruta'], ...]
 * @var array    $specs   [['nombre', 'valor'], ...]
 * @var array    $arbol
 * @var array    $marcas
 */
$err = fn(string $c) => isset($errores[$c]) ? '<p class="campo__error" id="f-' . $c . '-err">' . e($errores[$c]) . '</p>' : '';
$inv = fn(string $c) => isset($errores[$c]) ? ' aria-invalid="true" aria-describedby="f-' . $c . '-err"' : '';
$cls = fn(string $c) => isset($errores[$c]) ? ' campo--error' : '';
$num = fn($v) => ($v === null || (float) $v == 0) ? '' : rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
if (!$specs) {
    $specs = [['nombre' => '', 'valor' => '']];
}
?>
<div class="encabezado">
  <h1><?= e($titulo) ?></h1>
  <div class="encabezado__acciones">
    <?php if ($id && $p['activo']): ?>
      <a class="btn btn--sec" href="<?= e(url_producto($p)) ?>" target="_blank" rel="noopener">Ver en la tienda</a>
    <?php endif; ?>
    <a class="link" href="<?= e(admin_url('productos.php')) ?>">Volver a productos</a>
  </div>
</div>

<?php if ($errores): ?><p class="aviso aviso--error" role="alert">Revisá los datos marcados.</p><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="producto-form">
  <?= csrf_field() ?>
  <input type="hidden" name="accion" value="guardar">

  <div class="producto-form__principal">
    <section class="tarjeta form" aria-labelledby="t-datos">
      <h2 id="t-datos">Datos</h2>
      <div class="form__grilla">
        <div class="campo campo--ancho<?= $cls('nombre') ?>">
          <label for="f-nombre">Nombre</label>
          <input id="f-nombre" name="nombre" value="<?= e($p['nombre']) ?>" required maxlength="160"<?= $inv('nombre') ?>>
          <?= $err('nombre') ?>
          <p class="campo__ayuda">Como lo busca el cliente: tipo, capacidad y color. La marca va aparte.</p>
        </div>
        <div class="campo<?= $cls('sku') ?>">
          <label for="f-sku">Código (SKU)</label>
          <input id="f-sku" name="sku" value="<?= e($p['sku']) ?>" required maxlength="40" class="mayusculas"<?= $inv('sku') ?>>
          <?= $err('sku') ?>
        </div>
        <div class="campo">
          <label for="f-slug">Dirección (slug) <span class="opcional">(opcional)</span></label>
          <input id="f-slug" name="slug" value="<?= e($p['slug']) ?>" maxlength="180" data-slug-desde="f-nombre">
          <p class="campo__ayuda">Si lo dejás vacío se arma con el nombre.</p>
        </div>
        <div class="campo<?= $cls('categoria_id') ?>">
          <label for="f-cat">Categoría</label>
          <select id="f-cat" name="categoria_id" required<?= $inv('categoria_id') ?>>
            <option value="">Elegí una categoría</option>
            <?php foreach ($arbol as $r): ?>
              <optgroup label="<?= e($r['nombre']) ?>">
                <?php if (!$r['hijos']): ?>
                  <option value="<?= (int) $r['id'] ?>"<?= (int) $p['categoria_id'] === (int) $r['id'] ? ' selected' : '' ?>><?= e($r['nombre']) ?></option>
                <?php endif; ?>
                <?php foreach ($r['hijos'] as $h): ?>
                  <option value="<?= (int) $h['id'] ?>"<?= (int) $p['categoria_id'] === (int) $h['id'] ? ' selected' : '' ?>><?= e($h['nombre']) ?></option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
          <?= $err('categoria_id') ?>
        </div>
        <div class="campo">
          <label for="f-marca">Marca <span class="opcional">(opcional)</span></label>
          <select id="f-marca" name="marca_id">
            <option value="">Sin marca</option>
            <?php foreach ($marcas as $m): ?>
              <option value="<?= (int) $m['id'] ?>"<?= (int) $p['marca_id'] === (int) $m['id'] ? ' selected' : '' ?>><?= e($m['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <label class="sr-only" for="f-marca-nueva">O una marca nueva</label>
          <input id="f-marca-nueva" name="marca_nueva" value="<?= e($p['marca_nueva'] ?? '') ?>" maxlength="80" placeholder="¿No está? Escribila acá" class="mt">
        </div>
        <div class="campo campo--ancho">
          <label for="f-desc">Descripción <span class="opcional">(opcional)</span></label>
          <textarea id="f-desc" name="descripcion" rows="5"><?= e($p['descripcion']) ?></textarea>
        </div>
      </div>
    </section>

    <section class="tarjeta form" aria-labelledby="t-fotos">
      <h2 id="t-fotos">Fotos</h2>
      <?php if ($fotos): ?>
        <ul class="fotos">
          <?php foreach ($fotos as $f): $provisoria = preg_match('#^productos/[^/]+\.svg$#', $f['ruta']) === 1; ?>
            <li class="foto">
              <img src="<?= e(img($f['ruta'])) ?>" alt="" width="140" height="140" loading="lazy">
              <label class="check check--chico"><input type="radio" name="principal" value="<?= e($f['ruta']) ?>"<?= $f['ruta'] === $p['imagen'] ? ' checked' : '' ?>> <span>Principal</span></label>
              <label class="check check--chico"><input type="checkbox" name="borrar_foto[]" value="<?= e($f['ruta']) ?>"> <span>Borrar</span></label>
              <?php if ($provisoria): ?><span class="foto__nota">Ilustración provisoria</span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <div class="campo<?= $cls('fotos') ?>">
        <label for="f-fotos">Agregar fotos</label>
        <input id="f-fotos" name="fotos[]" type="file" multiple accept="image/jpeg,image/png,image/webp,image/gif" data-vista-previa<?= $inv('fotos') ?>>
        <?= $err('fotos') ?>
        <p class="campo__ayuda">JPG, PNG o WebP de hasta 8 MB. Ideal: cuadradas, con fondo blanco. Se achican solas a 1000 px.
          La primera foto real reemplaza a la ilustración provisoria.</p>
        <div class="vista-previa" data-vista-previa-de="f-fotos"></div>
      </div>
    </section>

    <section class="tarjeta form" aria-labelledby="t-specs">
      <h2 id="t-specs">Características</h2>
      <p class="campo__ayuda">Aparecen como tabla en la ficha. Ej: Capacidad · 382 L. Las filas vacías no se guardan.</p>
      <div class="specs-editor" data-specs>
        <?php foreach ($specs as $s): ?>
          <div class="spec-fila" data-spec-fila>
            <label class="sr-only">Característica</label>
            <input name="spec_nombre[]" value="<?= e($s['nombre']) ?>" placeholder="Característica" maxlength="80">
            <label class="sr-only">Valor</label>
            <input name="spec_valor[]" value="<?= e($s['valor']) ?>" placeholder="Valor" maxlength="190">
            <button type="button" class="link link--peligro" data-spec-quitar>Quitar</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn--sec btn--chico" data-spec-agregar>Agregar característica</button>
    </section>
  </div>

  <aside class="producto-form__lateral">
    <section class="tarjeta form" aria-labelledby="t-precio">
      <h2 id="t-precio">Precio</h2>
      <div class="campo<?= $cls('precio') ?>">
        <label for="f-precio">Precio de venta (con IVA)</label>
        <input id="f-precio" name="precio" inputmode="decimal" value="<?= e($num($p['precio'])) ?>" required<?= $inv('precio') ?> data-precio>
        <?= $err('precio') ?>
      </div>
      <div class="campo<?= $cls('precio_lista') ?>">
        <label for="f-lista">Precio de lista, tachado <span class="opcional">(opcional)</span></label>
        <input id="f-lista" name="precio_lista" inputmode="decimal" value="<?= e($num($p['precio_lista'])) ?>"<?= $inv('precio_lista') ?> data-precio-lista>
        <?= $err('precio_lista') ?>
        <p class="campo__ayuda" data-descuento>Si es mayor al de venta se muestra tachado con el % OFF.</p>
      </div>
      <div class="campo">
        <label for="f-cuotas">Cuotas sin interés</label>
        <select id="f-cuotas" name="cuotas">
          <?php foreach (CUOTAS as $n => $t): ?>
            <option value="<?= $n ?>"<?= (int) $p['cuotas_sin_interes'] === $n ? ' selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo">
        <label for="f-iva">IVA</label>
        <select id="f-iva" name="iva">
          <?php foreach (IVAS as $v => $t): ?>
            <option value="<?= e($v) ?>"<?= (float) $p['iva'] === (float) $v ? ' selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="campo__ayuda">Para calcular el "precio sin impuestos nacionales".</p>
      </div>
    </section>

    <section class="tarjeta form" aria-labelledby="t-stock">
      <h2 id="t-stock">Stock y publicación</h2>
      <div class="campo<?= $cls('stock') ?>">
        <label for="f-stock">Stock (unidades)</label>
        <input id="f-stock" name="stock" type="number" min="0" step="1" value="<?= (int) $p['stock'] ?>"<?= $inv('stock') ?>>
        <?= $err('stock') ?>
        <p class="campo__ayuda">Con 0 se muestra "Sin stock" y no se puede agregar al carrito.</p>
      </div>
      <label class="check"><input type="checkbox" name="activo" value="1"<?= $p['activo'] ? ' checked' : '' ?>> <span>Publicado en la tienda</span></label>
      <label class="check"><input type="checkbox" name="envio_gratis" value="1"<?= $p['envio_gratis'] ? ' checked' : '' ?>> <span>Envío gratis (en las zonas que lo permiten)</span></label>
      <label class="check"><input type="checkbox" name="destacado" value="1"<?= $p['destacado'] ? ' checked' : '' ?>> <span>Mostrar en "Lo más buscado"</span></label>
    </section>

    <div class="acciones acciones--lateral">
      <button class="btn btn--primario btn--bloque" type="submit">Guardar producto</button>
      <?php if (!$id): ?>
        <button class="btn btn--sec btn--bloque" type="submit" name="despues" value="nuevo">Guardar y cargar otro</button>
      <?php endif; ?>
    </div>
  </aside>
</form>

<?php if ($id): ?>
  <form method="post" class="zona-peligro" data-confirmar="¿Borrar &quot;<?= e($p['nombre']) ?>&quot; con sus fotos? No se puede deshacer. Si solo querés que no se vea, destildá &quot;Publicado&quot;.">
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="borrar">
    <button type="submit" class="btn btn--peligro">Borrar producto</button>
  </form>
<?php endif; ?>
