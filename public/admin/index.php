<?php
require __DIR__ . '/../../app/admin/bootstrap.php';
require_once APP_DIR . '/pedidos.php';
if (!admin_hay_usuarios()) {
    redirect(admin_url('primer-usuario.php'));
}
$yo = admin_requerido();

$n = function (string $sql): int {
    try {
        return (int) q_val($sql);
    } catch (Throwable $ex) {
        return 0;   // tabla todavía no creada (falta una migración)
    }
};
$resumen = [
    'pedidos_nuevos' => $n("SELECT COUNT(*) FROM pedidos WHERE estado = 'nuevo'"),
    'pedidos_curso'  => $n("SELECT COUNT(*) FROM pedidos WHERE estado IN ('confirmado', 'preparando', 'enviado')"),
    'publicados'   => $n('SELECT COUNT(*) FROM productos WHERE activo = 1'),
    'ocultos'      => $n('SELECT COUNT(*) FROM productos WHERE activo = 0'),
    'sin_stock'    => $n('SELECT COUNT(*) FROM productos WHERE activo = 1 AND stock <= 0'),
    'sin_foto'     => $n("SELECT COUNT(*) FROM productos WHERE activo = 1 AND (imagen IS NULL OR imagen LIKE 'productos/%.svg')"),
    'arrep'        => $n("SELECT COUNT(*) FROM arrepentimientos WHERE estado = 'recibida'"),
    'suscriptores' => $n('SELECT COUNT(*) FROM newsletter'),
];
$pocoStock = q_all('SELECT id, nombre, sku, stock, imagen FROM productos WHERE activo = 1 AND stock <= 3 ORDER BY stock, nombre LIMIT 8');
try {
    $ultimosPedidos = q_all('SELECT * FROM pedidos ORDER BY id DESC LIMIT 6');
} catch (Throwable $ex) {
    $ultimosPedidos = [];
}
$arrepentimientos = q_all("SELECT id, codigo, nombre, created_at FROM arrepentimientos WHERE estado = 'recibida' ORDER BY created_at LIMIT 5");

// Datos de la tienda que siguen con valores de ejemplo
$pendientes = [];
if (preg_replace('/\D+/', '', tienda('whatsapp')) === '5491100000000' || strlen(preg_replace('/\D+/', '', tienda('whatsapp'))) < 10) {
    $pendientes[] = 'El número de WhatsApp es de ejemplo: los pedidos no le llegan a nadie.';
}
if (!correo_configurado()) {
    $pendientes[] = 'No hay casilla de correo configurada: los avisos de pedidos se guardan en Correos pero no salen.';
}
if (str_contains(tienda('direccion'), 'Ejemplo')) {
    $pendientes[] = 'La dirección del local es de ejemplo.';
}
if (str_contains(tienda('telefono'), '4000-0000')) {
    $pendientes[] = 'El teléfono es de ejemplo.';
}
if (tienda('cuit') === '') {
    $pendientes[] = 'Falta el CUIT y la razón social (van en el pie de la página).';
}
if ($n("SELECT COUNT(*) FROM paginas WHERE contenido LIKE '%[Completar%' OR contenido LIKE '%[dirección%'")) {
    $pendientes[] = 'Hay páginas con textos para completar (marcados con [ ]).';
}
if ($n("SELECT COUNT(*) FROM productos WHERE sku LIKE 'CC-%'") > 0) {
    $pendientes[] = 'Siguen cargados productos de ejemplo (códigos que empiezan con CC-).';
}

render_admin('inicio', compact('resumen', 'pocoStock', 'arrepentimientos', 'pendientes', 'ultimosPedidos'), 'Inicio', '');
