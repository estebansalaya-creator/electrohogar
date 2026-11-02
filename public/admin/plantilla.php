<?php
/** Descarga la plantilla CSV para importar productos. */
require __DIR__ . '/../../app/admin/bootstrap.php';
$yo = admin_requerido();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="plantilla_productos.csv"');
echo "\xEF\xBB\xBF";
readfile(APP_ROOT . '/database/plantilla_productos.csv');
