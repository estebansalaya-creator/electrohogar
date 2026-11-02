<?php
/**
 * Subida de imágenes desde el panel.
 * Se guardan en public/assets/img/subidas/<carpeta>/ con nombre generado por el sistema.
 * Si PHP tiene GD, la imagen se re-codifica (achica a $maximo px y quita datos ocultos);
 * si no, se guarda tal cual después de verificar que sea una imagen válida.
 */
declare(strict_types=1);

const IMAGEN_TIPOS = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
const IMAGEN_MAX_BYTES = 8 * 1024 * 1024;

/**
 * Guarda una imagen subida. Devuelve la ruta relativa a assets/img (para la base) o lanza RuntimeException
 * con un mensaje para mostrar.
 *
 * @param array $archivo una entrada de $_FILES (un solo archivo)
 */
function imagen_guardar_subida(array $archivo, string $carpeta, int $maximo = 1000): string
{
    $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen supera el máximo que acepta el servidor (' . ini_get('upload_max_filesize')
                . '). Achicala o subí el límite upload_max_filesize en php.ini.',
            UPLOAD_ERR_NO_FILE => 'No se eligió ninguna imagen.',
            default => 'No se pudo subir la imagen. Probá de nuevo.',
        });
    }
    if (!is_uploaded_file($archivo['tmp_name'])) {
        throw new RuntimeException('Archivo inválido.');
    }
    if (filesize($archivo['tmp_name']) > IMAGEN_MAX_BYTES) {
        throw new RuntimeException('La imagen es demasiado grande (máximo 8 MB).');
    }
    $info = @getimagesize($archivo['tmp_name']);
    if (!$info || !isset(IMAGEN_TIPOS[$info[2]])) {
        throw new RuntimeException('El archivo no es una imagen JPG, PNG, WebP o GIF.');
    }

    $carpeta = preg_replace('/[^a-z0-9_-]/', '', strtolower($carpeta)) ?: 'varias';
    $relDir = 'subidas/' . $carpeta . '/' . date('Y-m');
    $absDir = APP_ROOT . '/public/assets/img/' . $relDir;
    if (!is_dir($absDir) && !@mkdir($absDir, 0775, true)) {
        throw new RuntimeException('El servidor no tiene permiso para guardar imágenes (carpeta public/assets/img/subidas).');
    }
    $base = date('d-His') . '-' . bin2hex(random_bytes(4));

    if (function_exists('imagecreatefromstring')) {
        $origen = @imagecreatefromstring((string) file_get_contents($archivo['tmp_name']));
        if (!$origen) {
            throw new RuntimeException('No se pudo leer la imagen.');
        }
        [$w, $h] = [imagesx($origen), imagesy($origen)];
        $escala = min(1, $maximo / max($w, $h));
        $nw = max(1, (int) round($w * $escala));
        $nh = max(1, (int) round($h * $escala));
        $destino = imagecreatetruecolor($nw, $nh);
        // Fondo blanco para PNG/GIF con transparencia (las fichas tienen fondo claro)
        imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nw, $nh, $w, $h);
        if (function_exists('imagewebp')) {
            $nombre = $base . '.webp';
            $ok = imagewebp($destino, $absDir . '/' . $nombre, 84);
        } else {
            $nombre = $base . '.jpg';
            $ok = imagejpeg($destino, $absDir . '/' . $nombre, 86);
        }
        imagedestroy($origen);
        imagedestroy($destino);
        if (!$ok) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }
    } else {
        $nombre = $base . '.' . IMAGEN_TIPOS[$info[2]];
        if (!move_uploaded_file($archivo['tmp_name'], $absDir . '/' . $nombre)) {
            throw new RuntimeException('El servidor no tiene permiso para guardar imágenes.');
        }
    }
    return $relDir . '/' . $nombre;
}

/**
 * Reordena $_FILES['campo'] de una subida múltiple en una lista de archivos.
 */
function imagen_lista_subidas(string $campo): array
{
    $f = $_FILES[$campo] ?? null;
    if (!$f || !is_array($f['name'])) {
        return $f && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? [$f] : [];
    }
    $lista = [];
    foreach ($f['name'] as $i => $nombre) {
        if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $lista[] = [
            'name' => $nombre, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i],
            'error' => $f['error'][$i], 'size' => $f['size'][$i],
        ];
    }
    return $lista;
}

/** Borra del disco una imagen subida (solo dentro de subidas/). */
function imagen_borrar_archivo(?string $ruta): void
{
    if (!$ruta || !str_starts_with($ruta, 'subidas/') || str_contains($ruta, '..')) {
        return;
    }
    $abs = APP_ROOT . '/public/assets/img/' . $ruta;
    if (is_file($abs)) {
        @unlink($abs);
    }
}
