<?php
/**
 * Mercado Pago: credenciales de la cuenta del cliente y llamadas a la API.
 * Las credenciales se cargan desde el panel (solo el administrador principal) y se guardan cifradas
 * en la tabla "ajustes" (claves mp.*). El cobro (Checkout Pro) se arma sobre esto en la etapa siguiente.
 */
declare(strict_types=1);

const MP_API = 'https://api.mercadopago.com';

function mp_config(): array
{
    $c = (array) config('mp', []);
    return [
        'access_token'   => (string) (descifrar(isset($c['access_token']) ? (string) $c['access_token'] : null) ?? ''),
        'webhook_secret' => (string) (descifrar(isset($c['webhook_secret']) ? (string) $c['webhook_secret'] : null) ?? ''),
        'token_guardado' => (string) ($c['access_token'] ?? '') !== '',
        'secreto_guardado' => (string) ($c['webhook_secret'] ?? '') !== '',
        'cuenta_id'      => (string) ($c['cuenta_id'] ?? ''),
        'cuenta_nombre'  => (string) ($c['cuenta_nombre'] ?? ''),
        'cuenta_email'   => (string) ($c['cuenta_email'] ?? ''),
        'verificado'     => (string) ($c['verificado'] ?? ''),
        'api_url'        => rtrim((string) ($c['api_url'] ?? MP_API), '/'),
    ];
}

function mp_configurado(): bool
{
    return mp_config()['access_token'] !== '';
}

/** Credenciales de prueba: token "TEST-..." o cuenta de usuario de prueba (apodo "TESTUSER..."). */
function mp_es_prueba(string $token, string $apodo = ''): bool
{
    return str_starts_with($token, 'TEST-') || stripos($apodo, 'TEST') === 0;
}

/** Formato básico de un Access Token de Mercado Pago. */
function mp_token_valido_formato(string $t): bool
{
    return (bool) preg_match('/^(APP_USR|TEST)-[A-Za-z0-9-]{20,200}$/', $t);
}

/**
 * Llamada a la API. Devuelve [código HTTP, respuesta decodificada o null].
 * Código 0 = no hubo conexión (sin internet, DNS, certificado, etc.); en ese caso $error trae el detalle.
 */
function mp_api(string $metodo, string $ruta, ?array $cuerpo = null, ?string $token = null, ?string &$error = null): array
{
    $token ??= mp_config()['access_token'];
    $url = mp_config()['api_url'] . $ruta;
    $cabeceras = ['Authorization: Bearer ' . $token, 'Accept: application/json'];
    $datos = null;
    if ($cuerpo !== null) {
        $datos = json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $cabeceras[] = 'Content-Type: application/json';
    }
    $error = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $metodo,
            CURLOPT_HTTPHEADER     => $cabeceras,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 20,
        ]);
        if ($datos !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
        }
        $resp = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($resp === false) {
            $error = curl_error($ch);
            $codigo = 0;
        }
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => [
            'method'        => $metodo,
            'header'        => implode("\r\n", $cabeceras),
            'content'       => $datos ?? '',
            'timeout'       => 20,
            'ignore_errors' => true,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        $codigo = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                $codigo = (int) $m[1];
            }
        }
        if ($resp === false) {
            $error = error_get_last()['message'] ?? 'sin respuesta';
            $codigo = 0;
        }
    }
    $json = is_string($resp) && $resp !== '' ? json_decode($resp, true) : null;
    return [$codigo, is_array($json) ? $json : null];
}

/**
 * Comprueba un Access Token preguntándole a Mercado Pago de qué cuenta es.
 * @return array{ok:bool, sin_conexion?:bool, error?:string, id?:string, apodo?:string, email?:string, pais?:string, prueba?:bool}
 */
function mp_verificar_token(string $token): array
{
    [$codigo, $r] = mp_api('GET', '/users/me', null, $token, $error);
    if ($codigo === 0) {
        error_log('[Carlos Confort] Mercado Pago sin conexión: ' . $error);
        return ['ok' => false, 'sin_conexion' => true, 'error' => 'No se pudo conectar con Mercado Pago. Revisá la conexión a internet del servidor.'];
    }
    if ($codigo === 401 || $codigo === 403) {
        return ['ok' => false, 'error' => 'Mercado Pago rechazó el Access Token: no es válido o fue renovado.'];
    }
    if ($codigo !== 200 || !$r || empty($r['id'])) {
        return ['ok' => false, 'error' => "Mercado Pago respondió algo inesperado (código $codigo). Probá de nuevo en unos minutos."];
    }
    $apodo = (string) ($r['nickname'] ?? '');
    return [
        'ok'     => true,
        'id'     => (string) $r['id'],
        'apodo'  => $apodo,
        'email'  => (string) ($r['email'] ?? ''),
        'pais'   => (string) ($r['site_id'] ?? ''),
        'prueba' => mp_es_prueba($token, $apodo),
    ];
}
