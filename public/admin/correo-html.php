<?php
/**
 * Devuelve el HTML de un correo guardado para mostrarlo dentro de un iframe del panel.
 * Se sirve con una política de seguridad estricta: sin scripts, sin formularios y solo embebible en el panel.
 */
require __DIR__ . '/../../app/admin/bootstrap.php';
$yo = admin_requerido();

$html = q_val('SELECT html FROM correos WHERE id = ?', [param_int('id')]);
if ($html === null || $html === false) {
    http_response_code(404);
    exit;
}
header('Content-Type: text/html; charset=utf-8');
header("Content-Security-Policy: default-src 'none'; img-src * data:; style-src 'unsafe-inline'; frame-ancestors 'self'; sandbox allow-popups allow-popups-to-escape-sandbox");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: private, no-store');
header('Referrer-Policy: no-referrer');
// Los enlaces del correo se abren en otra pestaña
$base = '<base target="_blank">';
echo preg_match('/<head[^>]*>/i', $html) ? preg_replace('/<head([^>]*)>/i', '<head$1>' . $base, $html, 1) : $base . $html;
