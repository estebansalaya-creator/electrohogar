<?php
/**
 * ABM genérico del panel a partir de app/admin/entidades.php.
 */
declare(strict_types=1);

function entidades(): array
{
    static $e = null;
    return $e ??= require APP_DIR . '/admin/entidades.php';
}

/** Definición de una sección; 404 si no existe. */
function entidad(string $clave): array
{
    $e = entidades()[$clave] ?? null;
    if (!$e) {
        no_encontrado('Esa sección del panel no existe.');
    }
    return $e + [
        'clave'    => $clave,
        'consulta' => 'SELECT t.* FROM ' . $e['tabla'] . ' t',
        'crear'    => true,
        'editar'   => true,
        'borrar'   => true,
        'buscar'   => [],
        'filtro'   => null,
        'exportar' => null,
        'validar'  => null,
        'ver'      => null,
        'nueva'    => 'Nuevo',
    ];
}

/** Opciones de un campo select: [valor => texto]. */
function crud_opciones(array $campo): array
{
    if (isset($campo['opciones'])) {
        return $campo['opciones'];
    }
    if (isset($campo['sql'])) {
        $out = [];
        foreach (q_all($campo['sql']) as $r) {
            $out[(string) $r['id']] = $r['nombre'];
        }
        return $out;
    }
    return [];
}

/** Listado con búsqueda, filtro y paginación. */
function crud_listar(array $ent, string $buscar, string $filtro, int $pagina, int $porPagina = 30): array
{
    $where = [];
    $params = [];
    if ($buscar !== '' && $ent['buscar']) {
        $or = [];
        foreach ($ent['buscar'] as $col) {
            $or[] = "$col LIKE ?";
            $params[] = '%' . addcslashes($buscar, '%_\\') . '%';
        }
        $where[] = '(' . implode(' OR ', $or) . ')';
    }
    if ($ent['filtro'] && $filtro !== '' && array_key_exists($filtro, crud_opciones($ent['campos'][$ent['filtro']]))) {
        $where[] = 't.' . $ent['filtro'] . ' = ?';
        $params[] = $filtro;
    }
    $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $total = (int) q_val('SELECT COUNT(*) FROM (' . $ent['consulta'] . $sqlWhere . ') x', $params);
    $paginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min(max(1, $pagina), $paginas);
    $offset = ($pagina - 1) * $porPagina;
    $items = q_all($ent['consulta'] . $sqlWhere . ' ORDER BY ' . ($ent['orden'] ?? 't.id DESC') . " LIMIT $porPagina OFFSET $offset", $params);
    return compact('items', 'total', 'pagina', 'paginas');
}

function crud_obtener(array $ent, int $id): ?array
{
    return q_one('SELECT * FROM ' . $ent['tabla'] . ' WHERE id = ?', [$id]);
}

/**
 * Valida y guarda. Devuelve ['ok' => bool, 'errores' => [campo => mensaje], 'id' => int, 'datos' => valores cargados]
 */
function crud_guardar(array $ent, ?int $id, array $post): array
{
    $actual = $id ? crud_obtener($ent, $id) : null;
    $datos = [];
    $errores = [];

    foreach ($ent['campos'] as $nombre => $c) {
        $tipo = $c['tipo'];
        if ($tipo === 'lectura') {
            continue;
        }
        $label = $c['label'];
        $crudo = $post[$nombre] ?? null;
        $valor = null;

        switch ($tipo) {
            case 'bool':
                $valor = !empty($crudo) ? 1 : 0;
                break;
            case 'numero':
                $t = trim((string) $crudo);
                if ($t === '') {
                    $valor = $c['defecto'] ?? null;
                } elseif (!preg_match('/^-?\d+$/', $t)) {
                    $errores[$nombre] = "$label tiene que ser un número entero.";
                } else {
                    $valor = (int) $t;
                }
                break;
            case 'decimal':
                $t = trim((string) $crudo);
                if ($t === '') {
                    $valor = $c['defecto'] ?? null;
                } else {
                    $valor = parse_numero($t);
                    if ($valor === null) {
                        $errores[$nombre] = "$label tiene que ser un número (por ejemplo 1599999 o 1.599.999,50).";
                    }
                }
                break;
            case 'fecha':
                $t = trim((string) $crudo);
                if ($t === '') {
                    $valor = null;
                } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $t) || !checkdate((int) substr($t, 5, 2), (int) substr($t, 8, 2), (int) substr($t, 0, 4))) {
                    $errores[$nombre] = "$label no es una fecha válida.";
                } else {
                    $valor = $t;
                }
                break;
            case 'select':
                $t = trim((string) $crudo);
                $ops = crud_opciones($c);
                if ($t === '' && isset($c['nulo'])) {
                    $valor = null;
                } elseif (!array_key_exists($t, $ops)) {
                    $errores[$nombre] = "Elegí una opción en $label.";
                } else {
                    $valor = $t;
                }
                break;
            case 'imagen':
                $valor = $actual[$nombre] ?? null;
                if (!empty($post[$nombre . '__quitar'])) {
                    $valor = null;
                }
                $archivo = $_FILES[$nombre] ?? null;
                if ($archivo && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $valor = imagen_guardar_subida($archivo, $c['carpeta'] ?? $ent['clave']);
                    } catch (RuntimeException $ex) {
                        $errores[$nombre] = $ex->getMessage();
                    }
                }
                break;
            case 'html':
            case 'textarea':
                $valor = trim(str_replace("\r\n", "\n", (string) $crudo));
                $valor = $valor === '' ? null : $valor;
                break;
            case 'email':
                $valor = mb_strtolower(trim((string) $crudo));
                if ($valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    $errores[$nombre] = "$label no es un email válido.";
                }
                $valor = $valor === '' ? null : $valor;
                break;
            case 'slug':
                $t = trim((string) $crudo);
                $base = $t !== '' ? $t : (string) ($post[$c['desde'] ?? ''] ?? '');
                $valor = slugify($base);
                $valor = $valor === '' ? null : $valor;
                break;
            default: // texto
                $valor = trim((string) $crudo);
                if (!empty($c['mayusculas'])) {
                    $valor = mb_strtoupper($valor);
                }
                $valor = $valor === '' ? null : $valor;
        }

        if (!isset($errores[$nombre])) {
            if (!empty($c['requerido']) && ($valor === null || $valor === '')) {
                $errores[$nombre] = "Completá $label.";
            } elseif (isset($c['max']) && is_string($valor) && mb_strlen($valor) > $c['max']) {
                $errores[$nombre] = "$label puede tener hasta {$c['max']} caracteres.";
            } elseif (in_array($tipo, ['numero', 'decimal'], true) && $valor !== null) {
                if (isset($c['min']) && $valor < $c['min']) {
                    $errores[$nombre] = "$label no puede ser menor que {$c['min']}.";
                } elseif (isset($c['max']) && $valor > $c['max']) {
                    $errores[$nombre] = "$label no puede ser mayor que {$c['max']}.";
                }
            }
        }
        if (!isset($errores[$nombre]) && !empty($c['unico']) && $valor !== null) {
            $existe = q_val('SELECT id FROM ' . $ent['tabla'] . " WHERE $nombre = ? AND id <> ?", [$valor, $id ?? 0]);
            if ($existe) {
                $errores[$nombre] = "Ya hay otra {$ent['singular']} con ese valor en $label.";
            }
        }
        $datos[$nombre] = $valor;
    }

    if (!$errores && $ent['validar']) {
        $msg = ($ent['validar'])($datos, $id);
        if ($msg) {
            $errores['_'] = $msg;
        }
    }
    if ($errores) {
        return ['ok' => false, 'errores' => $errores, 'id' => $id, 'datos' => $datos];
    }

    $cols = array_keys($datos);
    if ($id) {
        $set = implode(', ', array_map(fn($c) => "`$c` = ?", $cols));
        q('UPDATE ' . $ent['tabla'] . " SET $set WHERE id = ?", [...array_values($datos), $id]);
    } else {
        $lista = implode(', ', array_map(fn($c) => "`$c`", $cols));
        $marcas = implode(', ', array_fill(0, count($cols), '?'));
        q('INSERT INTO ' . $ent['tabla'] . " ($lista) VALUES ($marcas)", array_values($datos));
        $id = (int) db()->lastInsertId();
    }
    // Si se reemplazó o quitó una imagen subida, se borra el archivo viejo
    foreach ($ent['campos'] as $nombre => $c) {
        if ($c['tipo'] === 'imagen' && $actual && ($actual[$nombre] ?? null) !== ($datos[$nombre] ?? null)) {
            imagen_borrar_archivo($actual[$nombre] ?? null);
        }
    }
    return ['ok' => true, 'errores' => [], 'id' => $id, 'datos' => $datos];
}

/** Borra si ninguna regla lo impide. */
function crud_borrar(array $ent, int $id): array
{
    if ($ent['borrar'] === false) {
        return ['ok' => false, 'mensaje' => 'En esta sección no se puede borrar.'];
    }
    $fila = crud_obtener($ent, $id);
    if (!$fila) {
        return ['ok' => false, 'mensaje' => 'Ya no existe.'];
    }
    if (is_array($ent['borrar'])) {
        foreach ($ent['borrar'] as [$sql, $mensaje]) {
            if ((int) q_val($sql, [$id]) > 0) {
                return ['ok' => false, 'mensaje' => $mensaje];
            }
        }
    }
    q('DELETE FROM ' . $ent['tabla'] . ' WHERE id = ?', [$id]);
    foreach ($ent['campos'] as $nombre => $c) {
        if ($c['tipo'] === 'imagen') {
            imagen_borrar_archivo($fila[$nombre] ?? null);
        }
    }
    return ['ok' => true, 'mensaje' => 'Se borró correctamente.'];
}

/** Valor de una celda del listado, listo para HTML. */
function crud_celda(array $ent, string $col, array $fila): string
{
    $v = $fila[$col] ?? null;
    $c = $ent['campos'][$col] ?? ['tipo' => 'texto'];
    $formato = $c['formato'] ?? null;
    if ($c['tipo'] === 'imagen') {
        return $v ? '<img class="miniatura" src="' . e(img($v)) . '" alt="" width="44" height="44" loading="lazy">' : '<span class="miniatura miniatura--vacia"></span>';
    }
    if ($v === null || $v === '') {
        return '<span class="tenue">—</span>';
    }
    if ($c['tipo'] === 'bool') {
        return $v ? '<span class="estado estado--si">Sí</span>' : '<span class="estado estado--no">No</span>';
    }
    if ($c['tipo'] === 'select') {
        $ops = crud_opciones($c);
        $txt = $ops[(string) $v] ?? (string) $v;
        return '<span class="estado estado--' . e(slugify((string) $v)) . '">' . e($txt) . '</span>';
    }
    if ($formato === 'dinero' || in_array($col, ['costo', 'valor'], true) && ($fila['tipo'] ?? '') !== 'porcentaje' && is_numeric($v)) {
        return e(money($v));
    }
    if ($col === 'valor' && ($fila['tipo'] ?? '') === 'porcentaje') {
        return e(rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',') . '%');
    }
    if ($c['tipo'] === 'fecha' || $formato === 'fecha') {
        return e(date('d/m/Y', strtotime((string) $v)));
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', (string) $v)) {
        return e(date('d/m/Y H:i', strtotime((string) $v)));
    }
    return e(mb_strimwidth((string) $v, 0, 70, '…'));
}
