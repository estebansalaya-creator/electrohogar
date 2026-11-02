<?php
/**
 * Confirmación y seguimiento de un pedido (enlace privado con token).
 */
require __DIR__ . '/../app/bootstrap.php';
require APP_DIR . '/pedidos.php';

$p = pedido_por_token(param('t'));
if (!$p) {
    no_encontrado('No encontramos ese pedido. Revisá el enlace o escribinos por WhatsApp.');
}
$items = pedido_items((int) $p['id']);
$recienCreado = ($_SESSION['ultimo_pedido'] ?? '') === $p['token'];

render('pedido-confirmado', [
    'p'            => $p,
    'items'        => $items,
    'recienCreado' => $recienCreado,
    'whatsapp'     => whatsapp_link(pedido_texto_whatsapp($p, $items)),
    'conCorreo'    => $p['email'] && correo_configurado(),
], ['titulo' => 'Pedido N° ' . pedido_numero($p), 'noindex' => true, 'clase' => 'pg-pedido']);
