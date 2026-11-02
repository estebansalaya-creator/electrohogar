<?php
require __DIR__ . '/../../app/admin/bootstrap.php';
require APP_DIR . '/admin/crud.php';
$yo = admin_requerido();

$ent = entidad(param('e'));
$buscar = mb_substr(param('q'), 0, 80);
$filtro = param('filtro');
$res = crud_listar($ent, $buscar, $filtro, param_int('pagina', 1));

render_admin('lista', compact('ent', 'buscar', 'filtro', 'res'), $ent['titulo'], $ent['clave']);
