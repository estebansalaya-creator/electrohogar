<?php
/**
 * Funciones generales: configuración, escape, URLs, precios, CSRF, vistas.
 */
declare(strict_types=1);

// ---------------------------------------------------------------
// Configuración
// ---------------------------------------------------------------
function config(string $key, $default = null)
{
    $value = $GLOBALS['config'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function tienda(string $campo): string
{
    return (string) config('tienda.' . $campo, '');
}

// ---------------------------------------------------------------
// Escape y texto
// ---------------------------------------------------------------
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function plural(int $n, string $singular, string $plural): string
{
    return $n === 1 ? $singular : $plural;
}

function es_slug(string $s): bool
{
    return $s !== '' && strlen($s) <= 180 && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $s) === 1;
}

function slugify(string $texto): string
{
    $texto = mb_strtolower(trim($texto));
    $mapa = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n', '"' => '', '″' => ''];
    $texto = strtr($texto, $mapa);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto) ?? '';
    return trim($texto, '-');
}

// ---------------------------------------------------------------
// URLs
// ---------------------------------------------------------------
function url(string $path = '', array $params = []): string
{
    $base = rtrim((string) config('app.base_url', ''), '/');
    $params = array_filter($params, fn($v) => $v !== null && $v !== '' && $v !== false);
    $query = $params ? '?' . http_build_query($params) : '';
    return $base . '/' . ltrim($path, '/') . $query;
}

/**
 * URL completa (con dominio), para emails y enlaces que salen del sitio.
 * Usa app.url_publica si está configurada (https://www.ejemplo.com.ar); si no, el dominio del pedido actual.
 */
function url_absoluta(string $path = '', array $params = []): string
{
    $base = rtrim((string) config('app.url_publica', ''), '/');
    if ($base === '' && !empty($_SERVER['HTTP_HOST'])) {
        $base = (es_https() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    }
    return $base . url($path, $params);
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = APP_ROOT . '/public/assets/' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return url('assets/' . $path) . '?v=' . $version;
}

/** Imagen de producto/categoría guardada como ruta relativa a assets/img/. */
function img(?string $ruta): string
{
    $ruta = $ruta ?: 'productos/sin-imagen.svg';
    if (preg_match('#^https?://#i', $ruta)) {
        return $ruta;
    }
    return asset('img/' . $ruta);
}

/** Enlace cargado en la base: absoluto (https://...) o relativo al sitio (productos.php?cat=...). */
function enlace(string $u): string
{
    return preg_match('#^(https?:)?//#i', $u) ? $u : url($u);
}

function url_producto(array $p): string
{
    return url('producto.php', ['slug' => $p['slug']]);
}

function url_categoria(string $slug, array $extra = []): string
{
    return url('productos.php', ['cat' => $slug] + $extra);
}

function redirect(string $to, int $status = 303): never
{
    header('Location: ' . $to, true, $status);
    exit;
}

function es_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function es_api(): bool
{
    return defined('API_REQUEST');
}

function ip_cliente(): string
{
    // Detrás de un proxy (Railway) todos los pedidos llegan desde la IP del proxy: la del cliente viene en
    // X-Real-IP (Railway la reescribe en su borde) o, si falta, en el primer valor de X-Forwarded-For.
    // Solo se confía en esas cabeceras con APP_PROXY=1; en IIS se usa la IP de la conexión.
    if (config('app.proxy')) {
        $candidatas = [$_SERVER['HTTP_X_REAL_IP'] ?? '', explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))[0]];
        foreach ($candidatas as $ip) {
            $ip = trim((string) $ip);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                return substr($ip, 0, 45);
            }
        }
    }
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** true si el pedido viene de la misma PC donde corre el sitio. */
function es_localhost(): bool
{
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
}

/** Lee un parámetro de GET/POST como string recortado. */
function param(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function param_int(string $key, int $default = 0): int
{
    $v = param($key, '');
    return ($v !== '' && preg_match('/^-?\d+$/', $v)) ? (int) $v : $default;
}

// ---------------------------------------------------------------
// Precios
// ---------------------------------------------------------------
/** $ 1.234.567 (o con decimales: $ 1.234,56) */
function money($monto, int $decimales = 0): string
{
    return '$' . "\u{00A0}" . number_format((float) $monto, $decimales, ',', '.');
}

/** Monto de cuota: muestra decimales solo si hacen falta. */
function money_cuota(float $monto): string
{
    $redondeado = round($monto, 2);
    return money($redondeado, floor($redondeado) == $redondeado ? 0 : 2);
}

function descuento_pct(array $p): int
{
    $lista = (float) $p['precio_lista'];
    $precio = (float) $p['precio'];
    if ($lista <= 0 || $lista <= $precio) {
        return 0;
    }
    return (int) round((1 - $precio / $lista) * 100);
}

function descuento_transferencia(): float
{
    return (float) config('ventas.descuento_transferencia', 0);
}

/**
 * Convierte un número escrito a mano o desde Excel: 1599999 · 1.599.999 · 1599999,50 · $ 1.599.999,50
 * Devuelve null si no es un número.
 */
function parse_numero($v): ?float
{
    $v = trim((string) $v);
    if ($v === '') {
        return null;
    }
    $v = preg_replace('/[^\d,.\-]/', '', $v) ?? '';
    if (str_contains($v, ',')) {                       // formato argentino: 1.599.999,50
        $v = str_replace(',', '.', str_replace('.', '', $v));
    } elseif (substr_count($v, '.') > 1 || preg_match('/^\d{1,3}\.\d{3}$/', $v)) { // 1.599.999 o 520.000
        $v = str_replace('.', '', $v);
    }
    return is_numeric($v) ? (float) $v : null;
}

function precio_transferencia(float $precio): float
{
    return round($precio * (1 - descuento_transferencia() / 100));
}

function precio_sin_impuestos(float $precio, float $iva = 21.0): float
{
    return round($precio / (1 + $iva / 100), 2);
}

function valor_cuota(float $precio, int $cuotas): float
{
    return $cuotas > 0 ? $precio / $cuotas : $precio;
}

// ---------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// ---------------------------------------------------------------
// Mensajes flash (sobreviven a un redirect)
// ---------------------------------------------------------------
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flash_mensajes(): array
{
    $m = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $m;
}

// ---------------------------------------------------------------
// WhatsApp
// ---------------------------------------------------------------
function whatsapp_link(string $texto = ''): string
{
    $numero = preg_replace('/\D+/', '', tienda('whatsapp'));
    return 'https://wa.me/' . $numero . ($texto !== '' ? '?text=' . rawurlencode($texto) : '');
}

// ---------------------------------------------------------------
// Vistas
// ---------------------------------------------------------------
/**
 * Renderiza una vista dentro del layout.
 * $meta: titulo, descripcion, clase (clase del <body>)
 */
function render(string $vista, array $vars = [], array $meta = []): void
{
    $meta += ['titulo' => null, 'descripcion' => null, 'clase' => '', 'noindex' => false];
    extract($vars, EXTR_SKIP);
    require APP_DIR . '/views/layout/header.php';
    require APP_DIR . '/views/' . $vista . '.php';
    require APP_DIR . '/views/layout/footer.php';
}

/** Incluye un parcial con variables propias. */
function partial(string $nombre, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require APP_DIR . '/views/partials/' . $nombre . '.php';
}

function render_error(int $codigo, string $titulo, string $texto, ?string $detalle = null): never
{
    if (!headers_sent()) {
        http_response_code($codigo);
    }
    ob_start();
    try {
        render('error', compact('codigo', 'titulo', 'texto', 'detalle'), ['titulo' => $titulo, 'noindex' => true]);
        ob_end_flush();
    } catch (Throwable $ex) {
        // Si falla el layout (por ejemplo, sin base de datos), mostramos algo mínimo.
        ob_end_clean();
        error_log('[Carlos Confort] Error al mostrar la pagina de error: ' . $ex->getMessage());
        echo '<!doctype html><meta charset="utf-8"><title>' . e($titulo) . '</title><h1>' . e($titulo) . '</h1><p>' . e($texto) . '</p>';
        if ($detalle) {
            echo '<pre>' . e($detalle) . '</pre>';
        }
    }
    exit;
}

function no_encontrado(string $texto = 'La página que buscás no existe o se movió.'): never
{
    render_error(404, 'No encontramos esta página', $texto);
}

function json_response(array $data, int $status = 200): never
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
