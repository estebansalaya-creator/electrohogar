<?php
/**
 * Punto de arranque: cada página pública hace
 *   require __DIR__ . '/../app/bootstrap.php';
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_DIR', __DIR__);

require APP_DIR . '/helpers.php';
require APP_DIR . '/iconos.php';

// Configuración (cada capa pisa a la anterior):
//   1. app/config.php      -> configuración completa (datos de la tienda, ventas, etc.).
//                             Si no existe, se parte de app/config.example.php.
//   2. config.local.php    -> (opcional, en la raíz del proyecto) constantes de esta instalación:
//                             DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, APP_TZ, APP_DEBUG, APP_BASE_URL,
//                             APP_URL, APP_KEY (llave de cifrado), APP_PROXY, MAIL_*.
//   3. Variables de entorno -> los mismos nombres (para Railway / Docker). Si no hay DB_*, se toman las de
//                             un MySQL de Railway: MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE
//                             o MYSQL_URL.
$configFile = APP_DIR . '/config.php';
$configLocal = APP_ROOT . '/config.local.php';
$entorno = static function (string $nombre): ?string {
    $v = getenv($nombre);
    return $v === false || $v === '' ? null : $v;
};
$hayEntorno = $entorno('DB_HOST') !== null || $entorno('MYSQLHOST') !== null || $entorno('MYSQL_URL') !== null;
if (is_file($configFile)) {
    $GLOBALS['config'] = require $configFile;
} elseif (is_file($configLocal) || $hayEntorno) {
    $GLOBALS['config'] = require APP_DIR . '/config.example.php';
} else {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Falta la configuración.\nCopiá app/config.example.php como app/config.php y completá los datos de la base.");
}
$constantes = [
    'DB_HOST'      => ['db', 'host'],
    'DB_PORT'      => ['db', 'port'],
    'DB_NAME'      => ['db', 'name'],
    'DB_USER'      => ['db', 'user'],
    'DB_PASS'      => ['db', 'pass'],
    'APP_TZ'       => ['app', 'timezone'],
    'APP_DEBUG'    => ['app', 'debug'],
    'APP_BASE_URL' => ['app', 'base_url'],
    'APP_URL'      => ['app', 'url_publica'],
    'APP_KEY'      => ['app', 'clave'],
    'APP_PROXY'    => ['app', 'proxy'],         // 1 = detrás de un proxy (Railway): IP real del cliente en X-Real-IP
    'MP_API_URL'   => ['mp', 'api_url'],        // solo para pruebas (servidor simulado)
    'MAIL_HOST'    => ['correo', 'host'],
    'MAIL_PORT'    => ['correo', 'puerto'],
    'MAIL_SECURE'  => ['correo', 'seguridad'],
    'MAIL_USER'    => ['correo', 'usuario'],
    'MAIL_PASS'    => ['correo', 'clave'],
    'MAIL_FROM'    => ['correo', 'remitente'],
    'MAIL_NOTIFY'  => ['correo', 'avisos'],
];
if (is_file($configLocal)) {
    require_once $configLocal;
    foreach ($constantes as $constante => [$seccion, $clave]) {
        if (defined($constante)) {
            $GLOBALS['config'][$seccion][$clave] = constant($constante);
        }
    }
}
// MySQL de Railway: MYSQL_URL (mysql://usuario:clave@host:puerto/base) y luego las variables sueltas
if ($url = $entorno('MYSQL_URL')) {
    $u = parse_url($url) ?: [];
    foreach (['host' => 'host', 'port' => 'port', 'user' => 'user', 'pass' => 'pass'] as $parte => $clave) {
        if (isset($u[$parte]) && $u[$parte] !== '') {
            $GLOBALS['config']['db'][$clave] = $parte === 'user' || $parte === 'pass' ? rawurldecode((string) $u[$parte]) : $u[$parte];
        }
    }
    if (!empty($u['path']) && trim($u['path'], '/') !== '') {
        $GLOBALS['config']['db']['name'] = trim($u['path'], '/');
    }
}
foreach (['MYSQLHOST' => 'host', 'MYSQLPORT' => 'port', 'MYSQLUSER' => 'user', 'MYSQLPASSWORD' => 'pass', 'MYSQLDATABASE' => 'name'] as $variable => $clave) {
    if (($v = $entorno($variable)) !== null) {
        $GLOBALS['config']['db'][$clave] = $v;
    }
}
foreach ($constantes as $variable => [$seccion, $clave]) {
    if (($v = $entorno($variable)) !== null) {
        $GLOBALS['config'][$seccion][$clave] = $v;
    }
}
// "0", "false", "no" en APP_DEBUG / APP_PROXY se leen como apagado
foreach (['debug', 'proxy'] as $clave) {
    if (isset($GLOBALS['config']['app'][$clave]) && is_string($GLOBALS['config']['app'][$clave])) {
        $GLOBALS['config']['app'][$clave] = filter_var($GLOBALS['config']['app'][$clave], FILTER_VALIDATE_BOOLEAN);
    }
}
unset($constantes, $entorno, $hayEntorno, $url, $u, $v);

date_default_timezone_set((string) config('app.timezone', 'America/Argentina/Buenos_Aires'));
mb_internal_encoding('UTF-8');

if (config('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}

require APP_DIR . '/cifrado.php';
require APP_DIR . '/db.php';
require APP_DIR . '/repositorio.php';
require APP_DIR . '/carrito.php';

// Datos de la tienda cargados desde el panel (tabla "ajustes"): pisan a app/config.php.
// Si la tabla no existe todavía o la base no responde, se sigue con app/config.php.
try {
    foreach (q_all('SELECT clave, valor FROM ajustes') as $ajuste) {
        [$seccion, $campo] = array_pad(explode('.', $ajuste['clave'], 2), 2, '');
        if (in_array($seccion, ['tienda', 'ventas', 'correo', 'mp'], true) && $campo !== '' && $ajuste['valor'] !== null) {
            $GLOBALS['config'][$seccion][$campo] = $seccion === 'ventas' ? (float) $ajuste['valor'] : $ajuste['valor'];
        }
    }
} catch (Throwable $ex) {
    // sin ajustes: no es un error grave
}

set_exception_handler(function (Throwable $ex): void {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'ERROR: ' . $ex->getMessage() . "\n");
        exit(1);
    }
    error_log('[Carlos Confort] ' . $ex);
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (es_api()) {
        json_response(['ok' => false, 'mensaje' => 'No pudimos procesar el pedido. Probá de nuevo en unos minutos.'], 500);
    }
    $detalle = config('app.debug') ? $ex->getMessage() . "\n" . $ex->getFile() . ':' . $ex->getLine() : null;
    render_error(500, 'Algo salió mal', 'No pudimos cargar esta página. Probá de nuevo en unos minutos.', $detalle);
});

if (PHP_SAPI !== 'cli') {
    // Sesiones en una carpeta propia del sitio (cada cliente la suya). Si no se puede escribir, usa la de PHP.
    $rutaSesiones = (string) config('app.session_path', '');
    if ($rutaSesiones !== '') {
        if (!is_dir($rutaSesiones)) {
            @mkdir($rutaSesiones, 0770, true);
        }
        if (is_dir($rutaSesiones) && is_writable($rutaSesiones)) {
            session_save_path($rutaSesiones);
        } else {
            error_log('[Carlos Confort] No se puede escribir en ' . $rutaSesiones . '; se usan las sesiones por defecto de PHP.');
        }
    }

    $basePath = rtrim((string) config('app.base_url', ''), '/');
    session_name('ccsid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $basePath === '' ? '/' : $basePath . '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => es_https(),
    ]);
    session_start();
}
