<?php
/**
 * Suscripción al newsletter.
 *   POST api/newsletter.php email=...
 * Responde JSON si se llama con fetch; si no, vuelve a la página anterior con un mensaje.
 */
define('API_REQUEST', true);
require __DIR__ . '/../../app/bootstrap.php';

$esFetch = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
$volver = function (bool $ok, string $mensaje, int $status = 200) use ($esFetch) {
    if ($esFetch) {
        json_response(['ok' => $ok, 'mensaje' => $mensaje], $status);
    }
    flash($ok ? 'ok' : 'error', $mensaje);
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    redirect($ref !== '' && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST) ? $ref : url(''));
};

if (!es_post()) {
    $volver(false, 'Usá el formulario para suscribirte.', 405);
}
if (!csrf_valido()) {
    $volver(false, 'La sesión venció. Recargá la página y probá de nuevo.', 419);
}
$email = mb_strtolower(param('email'));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
    $volver(false, 'Revisá el email: tiene que ser como nombre@ejemplo.com.', 422);
}

$nuevo = newsletter_suscribir($email);
$volver(true, $nuevo ? 'Listo, te suscribiste. Te vamos a escribir a ' . $email . '.' : 'Ese email ya estaba suscripto.');
