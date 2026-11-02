<?php
/**
 * Cifrado de credenciales guardadas en la base (token de Mercado Pago, contraseña SMTP).
 *
 * La llave NO está en la base: sale de la constante APP_KEY de config.local.php (o de app.clave en app/config.php).
 * Así, una copia de la base sola no alcanza para leer las credenciales.
 * Para generar una llave: php -r "echo base64_encode(random_bytes(32));"
 *
 * Formato guardado: "enc:v1:" + base64(nonce de 12 bytes + etiqueta de 16 bytes + texto cifrado), AES-256-GCM.
 * Si el servidor no tiene OpenSSL pero sí Sodium: "enc:s1:" + base64(nonce de 24 bytes + texto cifrado).
 */
declare(strict_types=1);

const CIFRADO_PREFIJO = 'enc:';

/** Llave de 32 bytes derivada de APP_KEY, o null si no está configurada. */
function cifrado_llave(): ?string
{
    $base = trim((string) config('app.clave', ''));
    if (strlen($base) < 32) {
        return null;
    }
    return hash_hkdf('sha256', $base, 32, 'electrohogar-credenciales');
}

/** Motivo por el que no se puede cifrar, o null si está todo listo. */
function cifrado_problema(): ?string
{
    if (cifrado_llave() === null) {
        return 'Falta la llave de cifrado: agregá APP_KEY en config.local.php (al menos 32 caracteres al azar).';
    }
    if (!function_exists('openssl_encrypt') && !function_exists('sodium_crypto_secretbox')) {
        return 'PHP no tiene las extensiones openssl ni sodium: activá extension=openssl en php.ini.';
    }
    return null;
}

function cifrado_disponible(): bool
{
    return cifrado_problema() === null;
}

function es_cifrado(?string $valor): bool
{
    return $valor !== null && str_starts_with($valor, CIFRADO_PREFIJO);
}

function cifrar(string $texto): string
{
    if ($p = cifrado_problema()) {
        throw new RuntimeException($p);
    }
    $llave = cifrado_llave();
    if (function_exists('openssl_encrypt')) {
        $nonce = random_bytes(12);
        $etiqueta = '';
        $cifrado = openssl_encrypt($texto, 'aes-256-gcm', $llave, OPENSSL_RAW_DATA, $nonce, $etiqueta, '', 16);
        if ($cifrado === false) {
            throw new RuntimeException('No se pudo cifrar el dato.');
        }
        return CIFRADO_PREFIJO . 'v1:' . base64_encode($nonce . $etiqueta . $cifrado);
    }
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return CIFRADO_PREFIJO . 's1:' . base64_encode($nonce . sodium_crypto_secretbox($texto, $nonce, $llave));
}

/**
 * Devuelve el texto original. Un valor sin cifrar (de versiones anteriores) se devuelve tal cual.
 * Devuelve null si no se puede descifrar (llave cambiada o perdida, o dato dañado).
 */
function descifrar(?string $valor): ?string
{
    if ($valor === null || $valor === '' || !es_cifrado($valor)) {
        return $valor;
    }
    $llave = cifrado_llave();
    if ($llave === null) {
        return null;
    }
    $version = substr($valor, strlen(CIFRADO_PREFIJO), 3);
    $datos = base64_decode(substr($valor, strlen(CIFRADO_PREFIJO) + 3), true);
    if ($datos === false) {
        return null;
    }
    if ($version === 'v1:' && function_exists('openssl_decrypt') && strlen($datos) > 28) {
        $r = openssl_decrypt(substr($datos, 28), 'aes-256-gcm', $llave, OPENSSL_RAW_DATA, substr($datos, 0, 12), substr($datos, 12, 16));
        return $r === false ? null : $r;
    }
    if ($version === 's1:' && function_exists('sodium_crypto_secretbox_open') && strlen($datos) > SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
        $r = sodium_crypto_secretbox_open(substr($datos, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($datos, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $llave);
        return $r === false ? null : $r;
    }
    return null;
}

/** Para mostrar un dato secreto sin revelarlo: "APP_USR-…a1b2". */
function enmascarar(string $secreto, int $visibles = 4): string
{
    if ($secreto === '') {
        return '';
    }
    $prefijo = preg_match('/^(APP_USR-|TEST-)/', $secreto, $m) ? $m[1] : '';
    return $prefijo . '…' . substr($secreto, -$visibles);
}
