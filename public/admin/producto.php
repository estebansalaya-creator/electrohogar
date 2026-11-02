<?php
/** Alta y edición de un producto: datos, precios, fotos y características. */
require __DIR__ . '/../../app/admin/bootstrap.php';
require APP_DIR . '/importador.php';   // producto_slug_unico()
$yo = admin_requerido();
admin_verificar_post();

$id = param_int('id') ?: null;
$p = $id ? q_one('SELECT * FROM productos WHERE id = ?', [$id]) : null;
if ($id && !$p) {
    no_encontrado('Ese producto no existe o fue borrado.');
}

const IVAS = ['21' => '21%', '10.5' => '10,5%', '27' => '27%', '0' => 'Exento'];
const CUOTAS = [0 => 'Sin cuotas sin interés', 3 => '3 cuotas', 6 => '6 cuotas', 9 => '9 cuotas', 12 => '12 cuotas', 18 => '18 cuotas'];

/** Fotos del producto: la principal (productos.imagen) + la galería, sin repetir. */
function fotos_producto(?array $p): array
{
    if (!$p) {
        return [];
    }
    $fotos = [];
    foreach (q_all('SELECT id, ruta FROM producto_imagenes WHERE producto_id = ? ORDER BY orden, id', [$p['id']]) as $f) {
        $fotos[$f['ruta']] = ['id' => (int) $f['id'], 'ruta' => $f['ruta']];
    }
    if ($p['imagen'] && !isset($fotos[$p['imagen']])) {
        $fotos = [$p['imagen'] => ['id' => 0, 'ruta' => $p['imagen']]] + $fotos;
    }
    return array_values($fotos);
}

$errores = [];

// ---------- Borrar producto ----------
if (es_post() && param('accion') === 'borrar' && $p) {
    foreach (fotos_producto($p) as $f) {
        imagen_borrar_archivo($f['ruta']);
    }
    q('DELETE FROM productos WHERE id = ?', [$id]);
    flash('ok', 'Borramos "' . $p['nombre'] . '".');
    redirect(admin_url('productos.php'));
}

// ---------- Guardar ----------
if (es_post() && param('accion') === 'guardar') {
    $d = [
        'sku'                => mb_strtoupper(mb_substr(param('sku'), 0, 40)),
        'nombre'             => mb_substr(param('nombre'), 0, 160),
        'slug'               => slugify(param('slug')),
        'categoria_id'       => param_int('categoria_id'),
        'marca_id'           => param_int('marca_id') ?: null,
        'descripcion'        => trim(str_replace("\r\n", "\n", (string) ($_POST['descripcion'] ?? ''))) ?: null,
        'precio'             => parse_numero(param('precio')),
        'precio_lista'       => param('precio_lista') === '' ? 0.0 : parse_numero(param('precio_lista')),
        'iva'                => array_key_exists(param('iva'), IVAS) ? (float) param('iva') : 21.0,
        'cuotas_sin_interes' => array_key_exists(param_int('cuotas'), CUOTAS) ? param_int('cuotas') : 0,
        'stock'              => param('stock') === '' ? 0 : param_int('stock', -1),
        'envio_gratis'       => empty($_POST['envio_gratis']) ? 0 : 1,
        'destacado'          => empty($_POST['destacado']) ? 0 : 1,
        'activo'             => empty($_POST['activo']) ? 0 : 1,
    ];
    $marcaNueva = mb_substr(param('marca_nueva'), 0, 80);

    if ($d['sku'] === '') {
        $errores['sku'] = 'Completá el código (SKU).';
    } elseif (q_val('SELECT id FROM productos WHERE sku = ? AND id <> ?', [$d['sku'], $id ?? 0])) {
        $errores['sku'] = 'Ya hay otro producto con ese código.';
    }
    if (mb_strlen($d['nombre']) < 3) {
        $errores['nombre'] = 'Completá el nombre del producto.';
    }
    if (!$d['categoria_id'] || !categoria_por_id($d['categoria_id'])) {
        $errores['categoria_id'] = 'Elegí una categoría.';
    }
    if ($d['precio'] === null || $d['precio'] <= 0) {
        $errores['precio'] = 'Escribí el precio (por ejemplo 1599999 o 1.599.999).';
    }
    if ($d['precio_lista'] === null || $d['precio_lista'] < 0) {
        $errores['precio_lista'] = 'El precio de lista no es un número válido.';
    } elseif ($d['precio_lista'] > 0 && $d['precio'] !== null && $d['precio_lista'] <= $d['precio']) {
        $errores['precio_lista'] = 'El precio de lista (tachado) tiene que ser mayor que el precio de venta. Si no hay descuento, dejalo vacío.';
    }
    if ($d['stock'] < 0) {
        $errores['stock'] = 'El stock tiene que ser un número entero, 0 o más.';
    }

    $fotosNuevas = imagen_lista_subidas('fotos');
    if (!$errores) {
        db()->beginTransaction();
        try {
            if ($marcaNueva !== '') {
                $slugMarca = slugify($marcaNueva);
                $d['marca_id'] = (int) (q_val('SELECT id FROM marcas WHERE slug = ?', [$slugMarca]) ?: 0);
                if (!$d['marca_id']) {
                    q('INSERT INTO marcas (nombre, slug) VALUES (?, ?)', [$marcaNueva, $slugMarca]);
                    $d['marca_id'] = (int) db()->lastInsertId();
                }
            }
            $marcaNombre = $d['marca_id'] ? (string) q_val('SELECT nombre FROM marcas WHERE id = ?', [$d['marca_id']]) : '';
            $d['slug'] = producto_slug_unico($d['slug'] !== '' ? $d['slug'] : slugify($d['nombre'] . ' ' . $marcaNombre), $id);

            $cols = array_keys($d);
            if ($id) {
                q('UPDATE productos SET ' . implode(', ', array_map(fn($c) => "`$c` = ?", $cols)) . ' WHERE id = ?', [...array_values($d), $id]);
            } else {
                q('INSERT INTO productos (' . implode(', ', array_map(fn($c) => "`$c`", $cols)) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')', array_values($d));
                $id = (int) db()->lastInsertId();
            }
            $p = q_one('SELECT * FROM productos WHERE id = ?', [$id]);

            // Características: se reemplazan todas
            q('DELETE FROM producto_specs WHERE producto_id = ?', [$id]);
            $nombres = (array) ($_POST['spec_nombre'] ?? []);
            $valores = (array) ($_POST['spec_valor'] ?? []);
            $orden = 0;
            foreach ($nombres as $i => $sn) {
                $sn = mb_substr(trim((string) $sn), 0, 80);
                $sv = mb_substr(trim((string) ($valores[$i] ?? '')), 0, 190);
                if ($sn !== '' && $sv !== '') {
                    q('INSERT INTO producto_specs (producto_id, nombre, valor, orden) VALUES (?, ?, ?, ?)', [$id, $sn, $sv, ++$orden]);
                }
            }

            // Fotos: borrar las marcadas
            $fotos = fotos_producto($p);
            $borrar = array_map('strval', (array) ($_POST['borrar_foto'] ?? []));
            foreach ($fotos as $f) {
                if (in_array($f['ruta'], $borrar, true)) {
                    q('DELETE FROM producto_imagenes WHERE producto_id = ? AND ruta = ?', [$id, $f['ruta']]);
                    imagen_borrar_archivo($f['ruta']);
                }
            }
            // Fotos: subir las nuevas
            $orden = (int) q_val('SELECT COALESCE(MAX(orden), 0) FROM producto_imagenes WHERE producto_id = ?', [$id]);
            $subidas = [];
            foreach ($fotosNuevas as $archivo) {
                try {
                    $ruta = imagen_guardar_subida($archivo, 'productos');
                    q('INSERT INTO producto_imagenes (producto_id, ruta, orden) VALUES (?, ?, ?)', [$id, $ruta, ++$orden]);
                    $subidas[] = $ruta;
                } catch (RuntimeException $ex) {
                    flash('error', ($archivo['name'] ?? 'Una foto') . ': ' . $ex->getMessage());
                }
            }
            // Foto principal: la elegida; si no hay, la actual; las ilustraciones provisorias (.svg)
            // se reemplazan solas por la primera foto real que se suba.
            $esProvisoria = fn(?string $r) => $r !== null && preg_match('#^productos/[^/]+\.svg$#', $r) === 1;
            $restantes = array_column(fotos_producto(['id' => $id, 'imagen' => null]), 'ruta');
            $principalActual = in_array((string) $p['imagen'], $borrar, true) ? null : $p['imagen'];
            $elegida = (string) param('principal');
            $elegidaValida = $elegida !== '' && (in_array($elegida, $restantes, true) || $elegida === $principalActual);
            if ($elegidaValida && !($esProvisoria($elegida) && $subidas)) {
                $principal = $elegida;
            } elseif ($principalActual && !$esProvisoria($principalActual)) {
                $principal = $principalActual;
            } else {
                $principal = $subidas[0] ?? ($restantes[0] ?? $principalActual);
            }
            q('UPDATE productos SET imagen = ? WHERE id = ?', [$principal, $id]);

            db()->commit();
            flash('ok', 'Guardamos "' . $d['nombre'] . '".');
            redirect(param('despues') === 'nuevo' ? admin_url('producto.php') : admin_url('producto.php', ['id' => $id]));
        } catch (Throwable $ex) {
            db()->rollBack();
            throw $ex;
        }
    }
    // Con errores: se muestran los datos que escribió
    $p = array_merge($p ?? [], $d, ['marca_nueva' => $marcaNueva]);
    $specsForm = [];
    foreach ((array) ($_POST['spec_nombre'] ?? []) as $i => $sn) {
        $specsForm[] = ['nombre' => (string) $sn, 'valor' => (string) ($_POST['spec_valor'][$i] ?? '')];
    }
    if ($fotosNuevas) {
        $errores['fotos'] = 'Volvé a elegir las fotos nuevas (por seguridad no se guardan si hay errores en el formulario).';
    }
}

$p = $p ?? ['id' => null, 'sku' => '', 'nombre' => '', 'slug' => '', 'categoria_id' => null, 'marca_id' => null, 'descripcion' => '',
    'precio' => null, 'precio_lista' => 0, 'iva' => 21, 'cuotas_sin_interes' => 6, 'stock' => 0, 'envio_gratis' => 0,
    'destacado' => 0, 'activo' => 1, 'imagen' => null];
$specs = $specsForm ?? ($id ? producto_specs($id) : []);

render_admin('producto', [
    'p'       => $p,
    'id'      => $id,
    'errores' => $errores,
    'fotos'   => fotos_producto($id ? q_one('SELECT * FROM productos WHERE id = ?', [$id]) : null),
    'specs'   => $specs,
    'arbol'   => categorias_arbol(),
    'marcas'  => q_all('SELECT id, nombre FROM marcas ORDER BY nombre'),
], $id ? 'Editar producto' : 'Nuevo producto', 'productos');
