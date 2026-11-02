<?php
/**
 * Importa o actualiza productos desde un CSV (por ejemplo, exportado de Excel).
 * También se puede hacer desde el panel: Administración > Importar.
 *
 * Uso (desde la carpeta del proyecto):
 *   php herramientas/importar_productos.php archivo.csv            -> importa
 *   php herramientas/importar_productos.php archivo.csv --simular  -> muestra qué haría, sin guardar
 *
 * En Windows, si php no está en el PATH:
 *   "C:\Program Files\PHP\php.exe" herramientas\importar_productos.php productos.csv
 *
 * Columnas y reglas: ver app/importador.php y la plantilla database/plantilla_productos.csv
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo desde la línea de comandos.');
}

require __DIR__ . '/../app/bootstrap.php';
require APP_DIR . '/importador.php';

$args = array_slice($argv, 1);
$simular = in_array('--simular', $args, true);
$archivo = current(array_filter($args, fn($a) => $a !== '--simular'));
if (!$archivo || !is_file($archivo)) {
    fwrite(STDERR, "Uso: php herramientas/importar_productos.php archivo.csv [--simular]\n");
    exit(1);
}

try {
    $r = importar_csv($archivo, $simular);
} catch (RuntimeException $ex) {
    fwrite(STDERR, $ex->getMessage() . "\n");
    exit(1);
}

echo ($r['simulado'] ? "SIMULACIÓN (no se guardó nada)\n" : "Importación terminada\n");
echo "  Productos nuevos:       {$r['insertados']}\n";
echo "  Productos actualizados: {$r['actualizados']}\n";
if ($r['categorias']) {
    echo '  Categorías creadas:     ' . implode(', ', $r['categorias']) . "\n";
}
if ($r['marcas']) {
    echo '  Marcas creadas:         ' . implode(', ', $r['marcas']) . "\n";
}
if ($r['errores']) {
    echo '  Filas con error:        ' . count($r['errores']) . "\n";
    foreach ($r['errores'] as $e) {
        echo "    - $e\n";
    }
}
exit($r['errores'] ? 2 : 0);
