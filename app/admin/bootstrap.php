<?php
/**
 * Arranque del panel. Cada página de public/admin hace:
 *   require __DIR__ . '/../../app/admin/bootstrap.php';
 *   $yo = admin_requerido();   // (salvo login y primer usuario)
 */
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';
require APP_DIR . '/imagenes.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

const ADMIN_INACTIVIDAD = 2 * 3600;     // cierra la sesión después de 2 h sin uso
const ADMIN_MAX_INTENTOS = 8;           // intentos fallidos por IP...
const ADMIN_VENTANA_INTENTOS = 15;      // ...en estos minutos

function admin_url(string $pagina = '', array $params = []): string
{
    return url('admin/' . $pagina, $params);
}

/** Usuario logueado o null. */
function admin_actual(): ?array
{
    static $usuario = false;
    if ($usuario !== false) {
        return $usuario;
    }
    $usuario = null;
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }
    if (time() - (int) ($_SESSION['admin_ultimo'] ?? 0) > ADMIN_INACTIVIDAD) {
        admin_cerrar_sesion();
        flash('aviso', 'La sesión se cerró por inactividad. Ingresá de nuevo.');
        return null;
    }
    try {
        $usuario = q_one('SELECT id, nombre, email, principal FROM admin_usuarios WHERE id = ? AND activo = 1', [$id]);
    } catch (PDOException $ex) {
        // Base sin la migración 003 (todavía no existe la columna "principal")
        $usuario = q_one('SELECT id, nombre, email, 0 AS principal FROM admin_usuarios WHERE id = ? AND activo = 1', [$id]);
    }
    if ($usuario) {
        $_SESSION['admin_ultimo'] = time();
    } else {
        admin_cerrar_sesion();
    }
    return $usuario;
}

/** Exige sesión iniciada; si no, manda al login. */
function admin_requerido(): array
{
    $u = admin_actual();
    if (!$u) {
        $volver = $_SERVER['REQUEST_URI'] ?? '';
        redirect(admin_url('login.php', ['volver' => es_post() ? null : $volver]));
    }
    return $u;
}

/** Para formularios del panel: corta si el token CSRF no es válido. */
function admin_verificar_post(): void
{
    if (!es_post()) {
        return;
    }
    // Si lo enviado supera post_max_size, PHP descarta todo el formulario
    if (!$_POST && !$_FILES && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('error', 'Lo que enviaste supera el máximo que acepta el servidor (' . ini_get('post_max_size')
            . '). Subí menos fotos a la vez o aumentá post_max_size en php.ini.');
        redirect($_SERVER['REQUEST_URI'] ?? admin_url());
    }
    if (!csrf_valido()) {
        flash('error', 'La sesión venció o el formulario es viejo. Probá de nuevo.');
        redirect($_SERVER['REQUEST_URI'] ?? admin_url());
    }
}

/** El administrador principal es el único que puede ver y cambiar las credenciales de cobro. */
function admin_es_principal(?array $u): bool
{
    return $u !== null && (int) ($u['principal'] ?? 0) === 1;
}
function admin_principal_requerido(): array
{
    $u = admin_requerido();
    if (!admin_es_principal($u)) {
        flash('error', 'Esa sección solo la puede usar el administrador principal.');
        redirect(admin_url());
    }
    return $u;
}
/** Deja constancia de un cambio sensible (quién, qué, desde dónde). Nunca guardar claves en $detalle. */
function admin_registrar(array $u, string $accion, ?string $detalle = null): void
{
    q('INSERT INTO admin_registro (usuario_id, usuario, accion, detalle, ip) VALUES (?, ?, ?, ?, ?)',
        [$u['id'], mb_substr($u['nombre'], 0, 80), $accion, $detalle !== null ? mb_substr($detalle, 0, 255) : null, ip_cliente()]);
}
/** Confirma la contraseña del usuario antes de un cambio sensible. Cuenta los fallos igual que el ingreso. */
function admin_confirmar_clave(array $u, string $clave): ?string
{
    if (admin_bloqueado()) {
        return 'Demasiados intentos fallidos. Esperá ' . ADMIN_VENTANA_INTENTOS . ' minutos y probá de nuevo.';
    }
    $hash = (string) q_val('SELECT password_hash FROM admin_usuarios WHERE id = ?', [$u['id']]);
    if ($clave === '' || !password_verify($clave, $hash)) {
        admin_registrar_intento($u['email']);
        return 'La contraseña no es correcta.';
    }
    return null;
}

function admin_iniciar_sesion(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $u['id'];
    $_SESSION['admin_ultimo'] = time();
    unset($_SESSION['csrf']);           // token nuevo después de ingresar
    q('UPDATE admin_usuarios SET ultimo_acceso = NOW() WHERE id = ?', [$u['id']]);
}

function admin_cerrar_sesion(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_ultimo']);
    session_regenerate_id(true);
}

function admin_bloqueado(): bool
{
    $n = (int) q_val(
        'SELECT COUNT(*) FROM admin_intentos WHERE ip = ? AND created_at > (NOW() - INTERVAL ' . ADMIN_VENTANA_INTENTOS . ' MINUTE)',
        [ip_cliente()]
    );
    return $n >= ADMIN_MAX_INTENTOS;
}

function admin_registrar_intento(string $email): void
{
    q('INSERT INTO admin_intentos (ip, email) VALUES (?, ?)', [ip_cliente(), mb_substr($email, 0, 190)]);
    q('DELETE FROM admin_intentos WHERE created_at < (NOW() - INTERVAL 1 DAY)');
}

function admin_hay_usuarios(): bool
{
    return (int) q_val('SELECT COUNT(*) FROM admin_usuarios') > 0;
}

/** Valida una contraseña nueva. Devuelve el mensaje de error o null. */
function admin_validar_clave(string $clave, string $repetida): ?string
{
    if (mb_strlen($clave) < 10) {
        return 'La contraseña tiene que tener al menos 10 caracteres.';
    }
    if ($clave !== $repetida) {
        return 'Las dos contraseñas no coinciden.';
    }
    return null;
}

/** Destino seguro después del login (solo rutas internas del panel). */
function admin_destino(string $volver): string
{
    $base = rtrim((string) config('app.base_url', ''), '/') . '/admin/';
    return (str_starts_with($volver, $base) && strpos($volver, '//', 1) === false) ? $volver : admin_url();
}

/**
 * Renderiza una vista del panel con su layout.
 */
function render_admin(string $vista, array $vars = [], string $titulo = 'Panel', string $seccion = ''): void
{
    $yo = admin_actual();
    extract($vars, EXTR_SKIP);
    require APP_DIR . '/views/admin/layout/header.php';
    require APP_DIR . '/views/admin/' . $vista . '.php';
    require APP_DIR . '/views/admin/layout/footer.php';
}
