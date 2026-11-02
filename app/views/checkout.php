<?php
/**
 * @var array $r       resumen del carrito
 * @var array $datos
 * @var array $errores
 */
$campo = function (string $n, string $label, string $type = 'text', bool $req = true, string $extra = '', string $ayuda = '') use ($datos, $errores): string {
    $err = $errores[$n] ?? null;
    $id = 'c-' . $n;
    $desc = trim(($err ? "$id-err " : '') . ($ayuda ? "$id-ayuda" : ''));
    $h = '<div class="campo' . ($err ? ' campo--error' : '') . '">';
    $h .= '<label for="' . $id . '">' . e($label) . ($req ? '' : ' <span class="campo__opc">(opcional)</span>') . '</label>';
    $h .= '<input id="' . $id . '" name="' . $n . '" type="' . $type . '" value="' . e($datos[$n]) . '" ' . $extra
        . ($req ? ' required' : '') . ($err ? ' aria-invalid="true"' : '') . ($desc ? ' aria-describedby="' . $desc . '"' : '') . '>';
    if ($err) {
        $h .= '<p class="campo__error" id="' . $id . '-err">' . e($err) . '</p>';
    }
    if ($ayuda) {
        $h .= '<p class="campo__ayuda" id="' . $id . '-ayuda">' . e($ayuda) . '</p>';
    }
    return $h . '</div>';
};
$productosNeto = $r['subtotal'] - $r['descuento'];
?>
<div class="wrap">
  <?php partial('migas', ['migas' => [['Carrito', url('carrito.php')], ['Finalizar pedido', null]]]); ?>
  <h1 class="pagina__titulo">Finalizar pedido</h1>

  <div class="carrito checkout">
    <form class="formulario checkout__form" method="post" action="<?= e(url('checkout.php')) ?>" novalidate data-checkout>
      <?= csrf_field() ?>
      <div class="campo campo--trampa" aria-hidden="true">
        <label for="c-sitio">No completar</label>
        <input id="c-sitio" name="sitio_web" type="text" tabindex="-1" autocomplete="off">
      </div>
      <?php if (!empty($errores['_'])): ?>
        <p class="flash flash--error" role="alert"><?= e($errores['_']) ?></p>
      <?php elseif ($errores): ?>
        <p class="flash flash--error" role="alert">Revisá los datos marcados.</p>
      <?php endif; ?>

      <fieldset class="bloque">
        <legend class="bloque__titulo">Tus datos</legend>
        <div class="formulario__grid">
          <?= $campo('nombre', 'Nombre y apellido', 'text', true, 'autocomplete="name" maxlength="120"') ?>
          <?= $campo('telefono', 'Teléfono o WhatsApp', 'tel', true, 'autocomplete="tel" maxlength="40"', 'Con código de área. Te escribimos para confirmar.') ?>
        </div>
        <?= $campo('email', 'Email', 'email', false, 'autocomplete="email" maxlength="190"', 'Para mandarte el detalle del pedido.') ?>
      </fieldset>

      <fieldset class="bloque">
        <legend class="bloque__titulo">Entrega</legend>
        <div class="opciones-entrega">
          <label class="opcion">
            <input type="radio" name="entrega" value="envio"<?= $datos['entrega'] === 'envio' ? ' checked' : '' ?> data-entrega>
            <span><strong>Envío a domicilio</strong><span class="opcion__det">A todo el país. El costo depende del código postal.</span></span>
          </label>
          <label class="opcion">
            <input type="radio" name="entrega" value="retiro"<?= $datos['entrega'] === 'retiro' ? ' checked' : '' ?> data-entrega>
            <span><strong>Retiro en el local</strong><span class="opcion__det">Sin cargo. <?= e(tienda('direccion')) ?>.</span></span>
          </label>
        </div>
        <div class="datos-envio" data-datos-envio<?= $datos['entrega'] === 'retiro' ? ' hidden' : '' ?>>
          <div class="formulario__grid">
            <?= $campo('direccion', 'Calle, número, piso y depto.', 'text', true, 'autocomplete="street-address" maxlength="190"') ?>
            <?= $campo('localidad', 'Localidad', 'text', true, 'autocomplete="address-level2" maxlength="120"') ?>
          </div>
          <?= $campo('cp', 'Código postal', 'text', true, 'autocomplete="postal-code" inputmode="numeric" maxlength="8"') ?>
        </div>
      </fieldset>

      <fieldset class="bloque">
        <legend class="bloque__titulo">Comentarios <span class="campo__opc">(opcional)</span></legend>
        <div class="campo">
          <label class="sr-only" for="c-comentarios">Comentarios</label>
          <textarea id="c-comentarios" name="comentarios" rows="3" maxlength="1000" placeholder="Horario para recibir, forma de pago que preferís, consultas…"><?= e($datos['comentarios']) ?></textarea>
        </div>
      </fieldset>

      <button class="btn btn--primario btn--lg checkout__enviar" type="submit">Confirmar pedido</button>
      <p class="campo__ayuda">Todavía no pagás nada. Te contactamos para confirmar stock, forma de pago y entrega.</p>
    </form>

    <aside class="resumen" aria-labelledby="t-resumen">
      <h2 class="resumen__titulo" id="t-resumen">Tu pedido</h2>
      <ul class="resumen__items">
        <?php foreach ($r['lineas'] as $l): $p = $l['producto']; ?>
          <li>
            <img src="<?= e(img($p['imagen'])) ?>" alt="" width="56" height="56" loading="lazy">
            <span class="resumen__item-nombre"><?= (int) $l['cantidad'] ?> x <?= e($p['nombre']) ?></span>
            <span class="resumen__item-precio"><?= money($l['subtotal']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <dl class="totales">
        <div><dt>Subtotal</dt><dd><?= money($r['subtotal']) ?></dd></div>
        <?php if ($r['descuento'] > 0): ?>
          <div class="txt-verde"><dt>Cupón <?= e($r['cupon']['codigo']) ?></dt><dd>-<?= money($r['descuento']) ?></dd></div>
        <?php endif; ?>
        <div data-fila-envio<?= $datos['entrega'] === 'retiro' ? ' hidden' : '' ?>><dt>Envío</dt><dd><?php
            if (!$r['envio']) { echo 'Según tu CP'; }
            elseif (!$r['envio']['ok']) { echo 'A cotizar'; }
            elseif ($r['envio']['gratis']) { echo '<span class="txt-verde">Gratis</span>'; }
            else { echo money($r['envio']['costo']); }
        ?></dd></div>
        <div data-fila-retiro<?= $datos['entrega'] === 'retiro' ? '' : ' hidden' ?>><dt>Retiro en el local</dt><dd>Sin cargo</dd></div>
        <div class="totales__total"><dt>Total</dt><dd data-total
            data-con-envio="<?= e(money($r['total'])) ?>" data-sin-envio="<?= e(money($productosNeto)) ?>"><?= money($datos['entrega'] === 'retiro' ? $productosNeto : $r['total']) ?></dd></div>
      </dl>
      <a class="btn btn--link" href="<?= e(url('carrito.php')) ?>"><?= icono('izq') ?> Volver al carrito</a>
    </aside>
  </div>
</div>
