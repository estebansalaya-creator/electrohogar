<?php
/**
 * Importación de productos desde CSV en dos pasos: primero se revisa (simulación), después se confirma.
 */
require __DIR__ . '/../../app/admin/bootstrap.php';
require APP_DIR . '/importador.php';
$yo = admin_requerido();
admin_verificar_post();

$dir = APP_ROOT . '/storage/importaciones';
$resultado = null;
$archivoToken = null;
$error = null;

if (es_post()) {
    $paso = param('paso');
    if ($paso === 'revisar') {
        $f = $_FILES['archivo'] ?? null;
        $ext = strtolower(pathinfo((string) ($f['name'] ?? ''), PATHINFO_EXTENSION));
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $error = 'Elegí el archivo CSV.';
        } elseif (!in_array($ext, ['csv', 'txt'], true)) {
            $error = 'El archivo tiene que ser .csv. En Excel: Archivo > Guardar como > "CSV (delimitado por comas)".';
        } elseif ($f['size'] > 10 * 1024 * 1024) {
            $error = 'El archivo es demasiado grande (máximo 10 MB).';
        } else {
            if (!is_dir($dir) && !@mkdir($dir, 0770, true)) {
                $error = 'El servidor no puede guardar el archivo (carpeta storage).';
            } else {
                $archivoToken = date('Ymd-His') . '-' . bin2hex(random_bytes(6));
                move_uploaded_file($f['tmp_name'], "$dir/$archivoToken.csv");
                try {
                    $resultado = importar_csv("$dir/$archivoToken.csv", true);
                    $_SESSION['importacion'] = $archivoToken;
                } catch (RuntimeException $ex) {
                    $error = $ex->getMessage();
                    @unlink("$dir/$archivoToken.csv");
                    $archivoToken = null;
                }
            }
        }
    } elseif ($paso === 'confirmar') {
        $token = (string) ($_SESSION['importacion'] ?? '');
        if ($token === '' || $token !== param('token') || !preg_match('/^[\w-]+$/', $token) || !is_file("$dir/$token.csv")) {
            $error = 'No encontramos el archivo revisado. Subilo de nuevo.';
        } else {
            $resultado = importar_csv("$dir/$token.csv", false);
            unset($_SESSION['importacion']);
            @unlink("$dir/$token.csv");
            flash('ok', "Importación terminada: {$resultado['insertados']} productos nuevos y {$resultado['actualizados']} actualizados.");
        }
    }
}

// Limpieza de archivos de revisiones que no se confirmaron (más de 1 día)
foreach (glob("$dir/*.csv") ?: [] as $viejo) {
    if (filemtime($viejo) < time() - 86400) {
        @unlink($viejo);
    }
}

render_admin('importar', compact('resultado', 'archivoToken', 'error'), 'Importar productos', 'importar');
