<?php
/**
 * @var array       $p
 * @var array       $items
 * @var array       $eventos
 * @var string      $numero
 * @var array       $correos
 * @var string|null $waCliente
 */
$estadosCorreo = ['pendiente' => 'Pendiente', 'enviado' => 'Enviado', 'error' => 'Con error', 'sin_configurar' => 'Sin enviar'];
$costoEnvio = $p['costo_envio'] === null ? '' : number_format((float) $p['costo_envio'], 0, '', '');
$saludo = 'Hola ' . explode(' ', $p['nombre'])[0] . ', te escribimos de ' . tienda('nombre') . ' por tu pedido N° ' . $numero . '.';
?>
<div class="encabezado">
  <h1>Pedido N° <?= e($numero) ?> <span class="estado estado--<?= e($p['estado']) ?>"><?= e(pedido_estado_nombre($p)) ?></span></h1>
  <div class="encabezado__acciones">
    <a class="btn btn--sec" href="<?= e(url('pedido.php', ['t' => $p['token']])) ?>" target="_blank" rel="noopener">Ver como el cliente</a>
    <a class="link" href="<?= e(admin_url('pedidos.php')) ?>">Volver a pedidos</a>
  </div>
</div>

<div class="producto-form">
  <div>
    <section class="tarjeta" aria-labelledby="t-items">
      <h2 id="t-items">Productos</h2>
      <div class="tabla-contenedor">
        <table class="tabla tabla--items">
          <thead>
            <tr><th scope="col">Producto</th><th scope="col" class="num">Precio</th><th scope="col" class="num">Cant.</th><th scope="col" class="num">Subtotal</th></tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td>
                  <?php if ($it['producto_id']): ?>
                    <a class="tabla__principal" href="<?= e(admin_url('producto.php', ['id' => $it['producto_id']])) ?>"><?= e($it['nombre']) ?></a>
                  <?php else: ?>
                    <span class="tabla__principal"><?= e($it['nombre']) ?></span>
                  <?php endif; ?>
                  <span class="tabla__sub"><?= e($it['sku']) ?><?= $it['marca'] ? ' · ' . e($it['marca']) : '' ?><?= $it['producto_id'] ? '' : ' · ya no está en el catálogo' ?></span>
                </td>
                <td class="num"><?= money($it['precio_unitario']) ?></td>
                <td class="num"><?= (int) $it['cantidad'] ?></td>
                <td class="num"><?= money($it['subtotal']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <dl class="totales-admin">
        <div><dt>Subtotal</dt><dd><?= money($p['subtotal']) ?></dd></div>
        <?php if ((float) $p['descuento'] > 0): ?>
          <div class="txt-verde"><dt>Cupón <?= e($p['cupon']) ?></dt><dd>-<?= money($p['descuento']) ?></dd></div>
        <?php endif; ?>
        <div><dt><?= $p['entrega'] === 'retiro' ? 'Retiro en el local' : 'Envío' . ($p['zona'] ? ' (' . e($p['zona']) . ')' : '') ?></dt>
          <dd><?= $p['entrega'] === 'retiro' ? 'Sin cargo' : ($p['costo_envio'] === null ? '<span class="txt-naranja">A cotizar</span>' : ((float) $p['costo_envio'] == 0 ? 'Gratis' : money($p['costo_envio']))) ?></dd></div>
        <div class="totales-admin__total"><dt>Total</dt><dd><?= money($p['total']) ?></dd></div>
        <?php if ((float) $p['total_transferencia'] < (float) $p['total']): ?>
          <div class="tenue"><dt>Con transferencia</dt><dd><?= money($p['total_transferencia']) ?></dd></div>
        <?php endif; ?>
        <?php if ((int) $p['cuotas'] > 1): ?>
          <div class="tenue"><dt>Cuotas sin interés</dt><dd>hasta <?= (int) $p['cuotas'] ?></dd></div>
        <?php endif; ?>
      </dl>

      <?php if ($p['entrega'] === 'envio'): ?>
        <form method="post" class="campo envio-admin">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="envio">
          <label for="costo_envio">Costo de envío</label>
          <div class="en-fila">
            <span aria-hidden="true">$</span>
            <input id="costo_envio" name="costo_envio" inputmode="decimal" value="<?= e($costoEnvio) ?>" size="10" placeholder="A cotizar" aria-describedby="costo-ayuda">
            <button type="submit" class="btn btn--sec btn--chico">Guardar envío</button>
          </div>
          <p class="campo__ayuda" id="costo-ayuda">Poné 0 si es gratis. Vacío = a cotizar. El total se recalcula; los precios de los productos no cambian.</p>
        </form>
      <?php endif; ?>
    </section>

    <section class="tarjeta" aria-labelledby="t-hist">
      <h2 id="t-hist">Historial</h2>
      <ol class="historial">
        <?php foreach (array_reverse($eventos) as $ev): ?>
          <li>
            <p class="historial__que">
              <?php if ($ev['estado']): ?><span class="estado estado--<?= e($ev['estado']) ?>"><?= e(pedido_estado_nombre($p, $ev['estado'])) ?></span><?php endif; ?>
              <?= $ev['nota'] ? e($ev['nota']) : '' ?>
            </p>
            <p class="tabla__sub"><?= e(date('d/m/Y H:i', strtotime($ev['created_at']))) ?> · <?= e($ev['usuario'] ?? '') ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>

    <section class="tarjeta" aria-labelledby="t-correos">
      <h2 id="t-correos">Emails de este pedido</h2>
      <?php if (!$correos): ?>
        <p class="tenue">No se generó ningún email<?= $p['email'] ? '' : ' (el cliente no dejó email)' ?>.</p>
      <?php else: ?>
        <ul class="lista-simple">
          <?php foreach ($correos as $c): ?>
            <li>
              <a href="<?= e(admin_url('correo.php', ['id' => $c['id']])) ?>"><?= e($c['asunto']) ?></a>
              <span class="tenue"><?= e($c['para']) ?></span>
              <span class="estado estado--correo-<?= e($c['estado']) ?>"><?= e($estadosCorreo[$c['estado']] ?? $c['estado']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>

  <div class="producto-form__lateral pedido-lateral">
    <section class="tarjeta" aria-labelledby="t-cliente">
      <h2 id="t-cliente">Cliente</h2>
      <p class="cliente__nombre"><?= e($p['nombre']) ?></p>
      <p><a href="tel:<?= e(preg_replace('/[^\d+]+/', '', $p['telefono'])) ?>"><?= e($p['telefono']) ?></a></p>
      <?php if ($p['email']): ?><p><a href="mailto:<?= e($p['email']) ?>"><?= e($p['email']) ?></a></p><?php endif; ?>
      <?php if ($waCliente): ?>
        <a class="btn btn--whatsapp btn--bloque mt" href="https://wa.me/<?= e($waCliente) ?>?text=<?= e(rawurlencode($saludo)) ?>" target="_blank" rel="noopener">Escribirle por WhatsApp</a>
      <?php else: ?>
        <p class="campo__ayuda">No pudimos armar el enlace de WhatsApp con ese teléfono: llamalo o escribile a mano.</p>
      <?php endif; ?>
      <hr>
      <p><strong><?= $p['entrega'] === 'retiro' ? 'Retira en el local' : 'Envío a domicilio' ?></strong></p>
      <?php if ($p['entrega'] === 'envio'): ?>
        <p><?= e($p['direccion'] ?? '') ?><br><?= e($p['localidad'] ?? '') ?><?= $p['cp'] ? ' (CP ' . e($p['cp']) . ')' : '' ?></p>
      <?php endif; ?>
      <?php if ($p['comentarios']): ?>
        <p class="campo__ayuda">Comentarios del cliente:</p>
        <p class="campo__lectura"><?= nl2br(e($p['comentarios'])) ?></p>
      <?php endif; ?>
      <p class="tabla__sub mt">Hecho el <?= e(date('d/m/Y \a \l\a\s H:i', strtotime($p['created_at']))) ?></p>
    </section>

    <section class="tarjeta" aria-labelledby="t-estado">
      <h2 id="t-estado">Cambiar estado</h2>
      <form method="post" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="estado">
        <div class="campo">
          <label for="estado">Estado</label>
          <select id="estado" name="estado">
            <?php foreach (PEDIDO_ESTADOS as $k => $t): ?>
              <option value="<?= e($k) ?>"<?= $p['estado'] === $k ? ' selected' : '' ?>><?= e(pedido_estado_nombre($p, $k)) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="campo__ayuda"><?= (int) $p['stock_descontado']
              ? 'El stock ya está descontado. Si lo cancelás o lo volvés a "Nuevo", se devuelve.'
              : 'Al pasarlo a Confirmado (o a un estado posterior) se descuenta del stock.' ?></p>
        </div>
        <div class="campo">
          <label for="nota">Nota para el historial <span class="opcional">(opcional, la ve solo el local)</span></label>
          <textarea id="nota" name="nota" rows="2" maxlength="500"></textarea>
        </div>
        <?php if ($p['email']): ?>
          <label class="check"><input type="checkbox" name="avisar" value="1" data-mostrar="#campo-mensaje"> Avisarle al cliente por email</label>
          <div class="campo" id="campo-mensaje">
            <label for="mensaje">Mensaje para el cliente <span class="opcional">(opcional, va en el email)</span></label>
            <textarea id="mensaje" name="mensaje" rows="3" maxlength="1000" placeholder="Ej: Te lo llevamos el jueves entre las 9 y las 13 h."></textarea>
          </div>
        <?php endif; ?>
        <button class="btn btn--primario" type="submit">Guardar estado</button>
      </form>
    </section>

    <section class="tarjeta" aria-labelledby="t-notas">
      <h2 id="t-notas">Notas internas</h2>
      <form method="post" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="notas">
        <div class="campo">
          <label class="sr-only" for="notas_internas">Notas internas</label>
          <textarea id="notas_internas" name="notas_internas" rows="4" maxlength="2000" placeholder="Forma de pago acordada, día de entrega, etc."><?= e($p['notas_internas'] ?? '') ?></textarea>
        </div>
        <button class="btn btn--sec btn--chico" type="submit">Guardar notas</button>
      </form>
    </section>
  </div>
</div>
