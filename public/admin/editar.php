<?php
require __DIR__ . '/../../app/admin/bootstrap.php';
require APP_DIR . '/admin/crud.php';
$yo = admin_requerido();
admin_verificar_post();

$ent = entidad(param('e'));
$id = param_int('id') ?: null;
$fila = $id ? crud_obtener($ent, $id) : null;
if ($id && !$fila) {
    no_encontrado('Ese registro no existe o fue borrado.');
}
if (($id && !$ent['editar']) || (!$id && !$ent['crear'])) {
    no_encontrado('En esta sección no se puede hacer eso.');
}

$errores = [];
if (es_post()) {
    $r = crud_guardar($ent, $id, $_POST);
    if ($r['ok']) {
        flash('ok', 'Guardado.');
        $sigue = param('despues') === 'nuevo' && $ent['crear']
            ? admin_url('editar.php', ['e' => $ent['clave']])
            : admin_url('lista.php', ['e' => $ent['clave']]);
        redirect($sigue);
    }
    $errores = $r['errores'];
    $fila = array_merge($fila ?? [], $r['datos']);   // conserva lo que escribió
}

// Valores por defecto en un alta
if (!$fila) {
    $fila = [];
    foreach ($ent['campos'] as $n => $c) {
        $fila[$n] = $c['defecto'] ?? null;
    }
}

$titulo = $id ? 'Editar ' . $ent['singular'] : $ent['nueva'];
render_admin('editar', compact('ent', 'id', 'fila', 'errores'), $titulo, $ent['clave']);
