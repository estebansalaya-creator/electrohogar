<?php
/** Descarga un CSV (separado por ; y con BOM, para que Excel lo abra bien). */
require __DIR__ . '/../../app/admin/bootstrap.php';
require APP_DIR . '/admin/crud.php';
$yo = admin_requerido();

$ent = entidad(param('e'));
if (!$ent['exportar']) {
    no_encontrado('Esta sección no se puede descargar.');
}
$cols = $ent['exportar'];
$filas = q_all($ent['consulta'] . ' ORDER BY ' . ($ent['orden'] ?? 't.id'));

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $ent['clave'] . '-' . date('Y-m-d') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, array_values($cols), ';', '"', '');
foreach ($filas as $f) {
    $linea = [];
    foreach (array_keys($cols) as $c) {
        $v = (string) ($f[$c] ?? '');
        // Evita que Excel interprete el texto como fórmula
        if ($v !== '' && in_array($v[0], ['=', '+', '-', '@'], true)) {
            $v = "'" . $v;
        }
        $linea[] = $v;
    }
    fputcsv($out, $linea, ';', '"', '');
}
fclose($out);
