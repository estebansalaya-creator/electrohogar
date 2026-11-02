<?php
/**
 * Importación de productos desde CSV (Excel guardado como CSV).
 * La usan herramientas/importar_productos.php (línea de comandos) y el panel (admin/importar.php).
 *
 * Columnas (primera fila = títulos; separador ; o , ; el orden no importa):
 *   sku*, nombre*, categoria*, precio*, rubro, marca, precio_lista, stock, cuotas,
 *   envio_gratis, destacado, imagen, descripcion, iva, activo        (* obligatorias)
 * - Si el SKU existe, actualiza solo las columnas que tienen dato; si no, crea el producto.
 * - Categorías y marcas que no existen se crean (buscando antes por slug y por nombre).
 */
declare(strict_types=1);

const IMPORTADOR_ALIAS = [
    'codigo' => 'sku', 'titulo' => 'nombre', 'categoria-padre' => 'rubro', 'precio-lista' => 'precio_lista',
    'precio-de-lista' => 'precio_lista', 'envio-gratis' => 'envio_gratis', 'cuotas-sin-interes' => 'cuotas',
    'alicuota-iva' => 'iva',
];

/**
 * @return array{insertados:int, actualizados:int, categorias:array, marcas:array, errores:array, simulado:bool}
 * @throws RuntimeException si el archivo no tiene el formato esperado
 */
function importar_csv(string $archivo, bool $simular = false): array
{
    $contenido = (string) file_get_contents($archivo);
    $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido) ?? '';    // BOM
    if (!mb_check_encoding($contenido, 'UTF-8')) {
        $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252'); // CSV de Excel en Windows
    }
    if (trim($contenido) === '') {
        throw new RuntimeException('El archivo está vacío.');
    }
    $primeraLinea = strtok($contenido, "\n");
    $sep = substr_count($primeraLinea, ';') >= substr_count($primeraLinea, ',') ? ';' : ',';

    $fh = fopen('php://memory', 'r+');
    fwrite($fh, $contenido);
    rewind($fh);

    $titulos = array_map(function ($t) {
        $t = slugify((string) $t);
        return IMPORTADOR_ALIAS[$t] ?? str_replace('-', '_', $t);
    }, fgetcsv($fh, 0, $sep, '"', '') ?: []);
    foreach (['sku', 'nombre', 'categoria', 'precio'] as $req) {
        if (!in_array($req, $titulos, true)) {
            throw new RuntimeException("Falta la columna obligatoria \"$req\". Columnas encontradas: " . implode(', ', $titulos));
        }
    }

    // Categorías indexadas por slug y por nombre normalizado
    $categorias = [];
    foreach (q_all('SELECT id, nombre, slug, parent_id FROM categorias') as $c) {
        $categorias[$c['slug']] = $c;
        $categorias[slugify($c['nombre'])] = $c;
    }
    $marcas = [];
    foreach (q_all('SELECT id, slug FROM marcas') as $m) {
        $marcas[$m['slug']] = (int) $m['id'];
    }

    $res = ['insertados' => 0, 'actualizados' => 0, 'categorias' => [], 'marcas' => [], 'errores' => [], 'simulado' => $simular];
    $linea = 1;

    db()->beginTransaction();
    try {
        while (($fila = fgetcsv($fh, 0, $sep, '"', '')) !== false) {
            $linea++;
            if (count(array_filter($fila, fn($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $r = [];
            foreach ($titulos as $i => $t) {
                $r[$t] = isset($fila[$i]) ? trim((string) $fila[$i]) : '';
            }
            $sku = mb_substr($r['sku'], 0, 40);
            $nombre = mb_substr($r['nombre'], 0, 160);
            $precio = parse_numero($r['precio']);
            if ($sku === '' || $nombre === '' || $r['categoria'] === '' || $precio === null) {
                $res['errores'][] = "Línea $linea: faltan sku, nombre, categoría o precio.";
                continue;
            }
            try {
                $padreId = !empty($r['rubro']) ? importador_categoria($r['rubro'], null, $categorias, $res['categorias']) : null;
                $catId = importador_categoria($r['categoria'], $padreId, $categorias, $res['categorias']);
                $marcaId = isset($r['marca']) ? importador_marca($r['marca'], $marcas, $res['marcas']) : null;
                $num = fn($k) => isset($r[$k]) && $r[$k] !== '' ? parse_numero($r[$k]) : null;
                $bool = function ($k) use ($r) {
                    $v = mb_strtolower(trim((string) ($r[$k] ?? '')));
                    return $v === '' ? null : (in_array($v, ['1', 'si', 'sí', 's', 'x', 'true', 'verdadero', 'yes'], true) ? 1 : 0);
                };

                $datos = [
                    'categoria_id'       => $catId,
                    'marca_id'           => $marcaId,
                    'nombre'             => $nombre,
                    'precio'             => $precio,
                    'precio_lista'       => $num('precio_lista'),
                    'stock'              => $num('stock') !== null ? (int) $num('stock') : null,
                    'cuotas_sin_interes' => $num('cuotas') !== null ? (int) $num('cuotas') : null,
                    'envio_gratis'       => $bool('envio_gratis'),
                    'destacado'          => $bool('destacado'),
                    'activo'             => $bool('activo'),
                    'imagen'             => ($r['imagen'] ?? '') !== '' ? $r['imagen'] : null,
                    'descripcion'        => ($r['descripcion'] ?? '') !== '' ? $r['descripcion'] : null,
                    'iva'                => $num('iva'),
                ];

                $existente = q_one('SELECT id FROM productos WHERE sku = ?', [$sku]);
                if ($existente) {
                    $set = [];
                    $params = [];
                    foreach ($datos as $col => $val) {
                        if ($val !== null) {
                            $set[] = "$col = ?";
                            $params[] = $val;
                        }
                    }
                    $params[] = $existente['id'];
                    q('UPDATE productos SET ' . implode(', ', $set) . ' WHERE id = ?', $params);
                    $res['actualizados']++;
                } else {
                    $slug = producto_slug_unico(slugify($nombre . ' ' . ($r['marca'] ?? '')));
                    q(
                        'INSERT INTO productos (categoria_id, marca_id, sku, nombre, slug, descripcion, precio_lista, precio, iva,
                                                cuotas_sin_interes, stock, envio_gratis, destacado, imagen, activo)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $catId, $marcaId, $sku, $nombre, $slug, $datos['descripcion'], $datos['precio_lista'] ?? 0, $precio,
                            $datos['iva'] ?? 21, $datos['cuotas_sin_interes'] ?? 0, $datos['stock'] ?? 0,
                            $datos['envio_gratis'] ?? 0, $datos['destacado'] ?? 0, $datos['imagen'], $datos['activo'] ?? 1,
                        ]
                    );
                    $res['insertados']++;
                }
            } catch (Throwable $ex) {
                $res['errores'][] = "Línea $linea ($sku): " . $ex->getMessage();
            }
        }
        $simular ? db()->rollBack() : db()->commit();
    } catch (Throwable $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $ex;
    }
    $res['categorias'] = array_values(array_unique($res['categorias']));
    $res['marcas'] = array_values(array_unique($res['marcas']));
    return $res;
}

function importador_categoria(string $nombre, ?int $padre, array &$categorias, array &$creadas): int
{
    $nombre = trim($nombre);
    $slug = slugify($nombre);
    if (isset($categorias[$slug])) {
        return (int) $categorias[$slug]['id'];
    }
    $orden = (int) q_val('SELECT COALESCE(MAX(orden), 0) + 1 FROM categorias');
    q('INSERT INTO categorias (parent_id, nombre, slug, orden, en_menu) VALUES (?, ?, ?, ?, 0)', [$padre, $nombre, $slug, $orden]);
    $id = (int) db()->lastInsertId();
    $categorias[$slug] = ['id' => $id, 'nombre' => $nombre, 'slug' => $slug, 'parent_id' => $padre];
    $creadas[] = $nombre;
    return $id;
}

function importador_marca(string $nombre, array &$marcas, array &$creadas): ?int
{
    $nombre = trim($nombre);
    if ($nombre === '') {
        return null;
    }
    $slug = slugify($nombre);
    if (!isset($marcas[$slug])) {
        q('INSERT INTO marcas (nombre, slug) VALUES (?, ?)', [$nombre, $slug]);
        $marcas[$slug] = (int) db()->lastInsertId();
        $creadas[] = $nombre;
    }
    return $marcas[$slug];
}

/** Slug libre para un producto (agrega -2, -3... si ya existe). */
function producto_slug_unico(string $base, ?int $excluirId = null): string
{
    $base = substr($base, 0, 170) ?: 'producto';
    $slug = $base;
    $n = 2;
    while (q_val('SELECT id FROM productos WHERE slug = ? AND id <> ?', [$slug, $excluirId ?? 0])) {
        $slug = $base . '-' . $n++;
    }
    return $slug;
}
