<?php
/**
 * @var array       $datos
 * @var array       $errores
 * @var string|null $codigo
 */
$campo = function (string $name, string $label, string $type = 'text', bool $req = true, string $extra = '') use ($datos, $errores): string {
    $err = $errores[$name] ?? null;
    $id = 'arr-' . $name;
    $html = '<div class="campo' . ($err ? ' campo--error' : '') . '">';
    $html .= '<label for="' . $id . '">' . e($label) . ($req ? '' : ' <span class="campo__opc">(opcional)</span>') . '</label>';
    $html .= '<input id="' . $id . '" name="' . $name . '" type="' . $type . '" value="' . e($datos[$name]) . '"' . ($req ? ' required' : '') . ' ' . $extra;
    $html .= $err ? ' aria-invalid="true" aria-describedby="' . $id . '-err"' : '';
    $html .= '>';
    if ($err) {
        $html .= '<p class="campo__error" id="' . $id . '-err">' . e($err) . '</p>';
    }
    return $html . '</div>';
};
?>
<div class="wrap wrap--angosto">
  <?php partial('migas', ['migas' => [['Botón de arrepentimiento', null]]]); ?>
  <article class="pagina">
    <h1 class="pagina__titulo">Botón de arrepentimiento</h1>

    <?php if ($codigo): ?>
      <div class="confirmacion" role="status">
        <p class="confirmacion__titulo"><?= icono('check') ?> Recibimos tu solicitud</p>
        <p>Tu código de trámite es</p>
        <p class="confirmacion__codigo"><?= e($codigo) ?></p>
        <p>Guardalo: te sirve para seguir el trámite. Te vamos a escribir por email con los pasos para devolver el producto.</p>
      </div>
    <?php else: ?>
      <div class="texto">
        <p>Si compraste a distancia (por la web, WhatsApp o teléfono), tenés <strong>10 días corridos</strong> desde que recibiste el producto para revocar la compra, sin explicar el motivo y sin costo. Completá el formulario y te damos un código de trámite en el momento.</p>
        <p>El producto tiene que estar en las mismas condiciones en que lo recibiste.</p>
      </div>

      <?php if (!empty($errores['general'])): ?>
        <p class="flash flash--error" role="alert"><?= e($errores['general']) ?></p>
      <?php elseif ($errores): ?>
        <p class="flash flash--error" role="alert">Revisá los datos marcados.</p>
      <?php endif; ?>

      <form class="formulario" method="post" action="<?= e(url('arrepentimiento.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="campo campo--trampa" aria-hidden="true">
          <label for="arr-sitio">No completar</label>
          <input id="arr-sitio" name="sitio_web" type="text" tabindex="-1" autocomplete="off">
        </div>
        <div class="formulario__grid">
          <?= $campo('nombre', 'Nombre y apellido', 'text', true, 'autocomplete="name" maxlength="120"') ?>
          <?= $campo('dni', 'DNI o CUIT', 'text', true, 'inputmode="numeric" maxlength="13"') ?>
          <?= $campo('email', 'Email', 'email', true, 'autocomplete="email" maxlength="190"') ?>
          <?= $campo('telefono', 'Teléfono', 'tel', false, 'autocomplete="tel" maxlength="40"') ?>
          <?= $campo('nro_pedido', 'Número de pedido o factura', 'text', true, 'maxlength="40"') ?>
          <?= $campo('fecha_compra', 'Fecha de compra', 'date', false, 'max="' . date('Y-m-d') . '"') ?>
        </div>
        <?= $campo('producto', 'Producto que querés devolver', 'text', false, 'maxlength="190"') ?>
        <div class="campo">
          <label for="arr-motivo">Comentarios <span class="campo__opc">(opcional)</span></label>
          <textarea id="arr-motivo" name="motivo" rows="4" maxlength="1000"><?= e($datos['motivo']) ?></textarea>
        </div>
        <button class="btn btn--primario btn--lg" type="submit">Enviar solicitud de arrepentimiento</button>
      </form>
    <?php endif; ?>
  </article>
</div>
