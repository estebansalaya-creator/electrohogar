<?php
/**
 * Secciones del panel que usan el ABM genérico (lista + formulario).
 * Productos tiene páginas propias (fotos, características).
 *
 * Tipos de campo: texto, slug, textarea, html, numero, decimal, bool, select, fecha, email, imagen, lectura
 * Opciones de campo: label, requerido, max, min, ayuda, defecto, mayusculas, unico, desde (slug), opciones/sql (select),
 *                    nulo (texto de la opción vacía en select), carpeta (imagen), formato (lista: dinero, fecha, fechahora)
 */
declare(strict_types=1);

return [
    'categorias' => [
        'titulo'   => 'Categorías',
        'singular' => 'categoría',
        'nueva'    => 'Nueva categoría',
        'tabla'    => 'categorias',
        'consulta' => 'SELECT t.*, p.nombre AS rubro FROM categorias t LEFT JOIN categorias p ON p.id = t.parent_id',
        'orden'    => 'COALESCE(p.orden, t.orden), t.parent_id IS NOT NULL, t.orden, t.nombre',
        'buscar'   => ['t.nombre', 't.slug'],
        'columnas' => ['imagen' => '', 'nombre' => 'Nombre', 'rubro' => 'Rubro', 'orden' => 'Orden', 'en_menu' => 'En el menú', 'activo' => 'Activa'],
        'campos'   => [
            'nombre'    => ['label' => 'Nombre', 'tipo' => 'texto', 'requerido' => true, 'max' => 80],
            'slug'      => ['label' => 'Dirección (slug)', 'tipo' => 'slug', 'desde' => 'nombre', 'unico' => true, 'max' => 90,
                            'ayuda' => 'Aparece en la dirección de la página. Si lo dejás vacío se arma con el nombre.'],
            'parent_id' => ['label' => 'Rubro', 'tipo' => 'select', 'nulo' => '— Es un rubro principal —',
                            'sql' => 'SELECT id, nombre FROM categorias WHERE parent_id IS NULL ORDER BY orden, nombre',
                            'ayuda' => 'Los rubros agrupan categorías en el menú (por ejemplo, Electrodomésticos > Heladeras).'],
            'imagen'    => ['label' => 'Imagen', 'tipo' => 'imagen', 'carpeta' => 'categorias',
                            'ayuda' => 'Se usa en "Comprá por categoría". Cuadrada, fondo claro.'],
            'orden'     => ['label' => 'Orden', 'tipo' => 'numero', 'min' => 0, 'max' => 9999, 'defecto' => 0],
            'en_menu'   => ['label' => 'Mostrar en la barra de categorías y en la home', 'tipo' => 'bool', 'defecto' => 0],
            'activo'    => ['label' => 'Activa', 'tipo' => 'bool', 'defecto' => 1],
        ],
        'validar'  => function (array $d, ?int $id): ?string {
            if ($id && (int) $d['parent_id'] === $id) {
                return 'Una categoría no puede ser rubro de sí misma.';
            }
            if ($id && $d['parent_id'] !== null && (int) q_val('SELECT COUNT(*) FROM categorias WHERE parent_id = ?', [$id]) > 0) {
                return 'Esta categoría tiene subcategorías: no puede quedar dentro de otro rubro.';
            }
            return null;
        },
        'borrar'   => [
            ['SELECT COUNT(*) FROM productos WHERE categoria_id = ?', 'Tiene productos. Pasalos a otra categoría antes de borrarla.'],
            ['SELECT COUNT(*) FROM categorias WHERE parent_id = ?', 'Tiene subcategorías. Borralas o movelas antes.'],
        ],
    ],

    'marcas' => [
        'titulo'   => 'Marcas',
        'singular' => 'marca',
        'nueva'    => 'Nueva marca',
        'tabla'    => 'marcas',
        'consulta' => 'SELECT t.*, (SELECT COUNT(*) FROM productos p WHERE p.marca_id = t.id) AS productos FROM marcas t',
        'orden'    => 't.nombre',
        'buscar'   => ['t.nombre'],
        'columnas' => ['nombre' => 'Nombre', 'slug' => 'Slug', 'productos' => 'Productos'],
        'campos'   => [
            'nombre' => ['label' => 'Nombre', 'tipo' => 'texto', 'requerido' => true, 'max' => 80],
            'slug'   => ['label' => 'Slug', 'tipo' => 'slug', 'desde' => 'nombre', 'unico' => true, 'max' => 90,
                         'ayuda' => 'Si lo dejás vacío se arma con el nombre.'],
        ],
        'borrar'   => [
            ['SELECT COUNT(*) FROM productos WHERE marca_id = ?', 'Hay productos de esta marca. Cambiales la marca antes de borrarla.'],
        ],
    ],

    'banners' => [
        'titulo'   => 'Banners de la home',
        'singular' => 'banner',
        'nueva'    => 'Nuevo banner',
        'tabla'    => 'banners',
        'orden'    => 't.orden, t.id',
        'buscar'   => ['t.titulo'],
        'columnas' => ['imagen' => '', 'titulo' => 'Título', 'tema' => 'Color', 'orden' => 'Orden', 'desde' => 'Desde', 'hasta' => 'Hasta', 'activo' => 'Activo'],
        'campos'   => [
            'titulo'         => ['label' => 'Título', 'tipo' => 'texto', 'requerido' => true, 'max' => 120],
            'subtitulo'      => ['label' => 'Bajada', 'tipo' => 'textarea', 'max' => 220],
            'texto_boton'    => ['label' => 'Texto del botón', 'tipo' => 'texto', 'max' => 40, 'ayuda' => 'Por ejemplo: Ver aires acondicionados'],
            'url'            => ['label' => 'Enlace del botón', 'tipo' => 'texto', 'max' => 255,
                                 'ayuda' => 'Una página del sitio (productos.php?cat=colchones) o una dirección completa (https://...).'],
            'imagen'         => ['label' => 'Imagen', 'tipo' => 'imagen', 'carpeta' => 'banners', 'ayuda' => 'Producto con fondo transparente o claro.'],
            'etiqueta_valor' => ['label' => 'Etiqueta: texto grande', 'tipo' => 'texto', 'max' => 12, 'ayuda' => 'Ej: 12 · 10% · Gratis'],
            'etiqueta_texto' => ['label' => 'Etiqueta: texto chico', 'tipo' => 'texto', 'max' => 40, 'ayuda' => 'Ej: cuotas sin interés'],
            'tema'           => ['label' => 'Color de fondo', 'tipo' => 'select', 'requerido' => true,
                                 'opciones' => ['azul' => 'Azul', 'naranja' => 'Naranja', 'claro' => 'Celeste claro'], 'defecto' => 'azul'],
            'orden'          => ['label' => 'Orden', 'tipo' => 'numero', 'min' => 0, 'max' => 999, 'defecto' => 0],
            'desde'          => ['label' => 'Mostrar desde', 'tipo' => 'fecha', 'ayuda' => 'Vacío: desde ya.'],
            'hasta'          => ['label' => 'Mostrar hasta', 'tipo' => 'fecha', 'ayuda' => 'Vacío: sin vencimiento.'],
            'activo'         => ['label' => 'Activo', 'tipo' => 'bool', 'defecto' => 1],
        ],
        'validar'  => fn(array $d) => ($d['desde'] && $d['hasta'] && $d['desde'] > $d['hasta']) ? 'La fecha "hasta" es anterior a "desde".' : null,
    ],

    'cupones' => [
        'titulo'   => 'Cupones',
        'singular' => 'cupón',
        'nueva'    => 'Nuevo cupón',
        'tabla'    => 'cupones',
        'orden'    => 't.activo DESC, t.codigo',
        'buscar'   => ['t.codigo'],
        'columnas' => ['codigo' => 'Código', 'tipo' => 'Tipo', 'valor' => 'Valor', 'minimo_compra' => 'Compra mínima', 'desde' => 'Desde', 'hasta' => 'Hasta', 'activo' => 'Activo'],
        'campos'   => [
            'codigo'        => ['label' => 'Código', 'tipo' => 'texto', 'requerido' => true, 'max' => 30, 'mayusculas' => true, 'unico' => true,
                                'ayuda' => 'Lo que el cliente escribe en el carrito. Sin espacios.'],
            'tipo'          => ['label' => 'Tipo de descuento', 'tipo' => 'select', 'requerido' => true,
                                'opciones' => ['porcentaje' => 'Porcentaje (%)', 'monto' => 'Monto fijo ($)'], 'defecto' => 'porcentaje'],
            'valor'         => ['label' => 'Valor', 'tipo' => 'decimal', 'requerido' => true, 'min' => 0.01, 'ayuda' => 'Ej: 5 (para 5%) o 20000 (para $ 20.000).'],
            'minimo_compra' => ['label' => 'Compra mínima', 'tipo' => 'decimal', 'min' => 0, 'defecto' => 0, 'formato' => 'dinero'],
            'desde'         => ['label' => 'Válido desde', 'tipo' => 'fecha'],
            'hasta'         => ['label' => 'Válido hasta', 'tipo' => 'fecha'],
            'activo'        => ['label' => 'Activo', 'tipo' => 'bool', 'defecto' => 1],
        ],
        'validar'  => function (array $d): ?string {
            if (preg_match('/\s/', (string) $d['codigo'])) {
                return 'El código no puede tener espacios.';
            }
            if ($d['tipo'] === 'porcentaje' && (float) $d['valor'] > 90) {
                return 'Un descuento porcentual no puede superar el 90%.';
            }
            return ($d['desde'] && $d['hasta'] && $d['desde'] > $d['hasta']) ? 'La fecha "hasta" es anterior a "desde".' : null;
        },
    ],

    'envios' => [
        'titulo'   => 'Zonas de envío',
        'singular' => 'zona',
        'nueva'    => 'Nueva zona',
        'tabla'    => 'zonas_envio',
        'orden'    => 't.cp_desde',
        'buscar'   => ['t.nombre'],
        'columnas' => ['nombre' => 'Zona', 'cp_desde' => 'CP desde', 'cp_hasta' => 'CP hasta', 'costo' => 'Costo', 'permite_gratis' => 'Envío gratis', 'dias' => 'Demora', 'prioridad' => 'Prioridad'],
        'campos'   => [
            'nombre'         => ['label' => 'Nombre de la zona', 'tipo' => 'texto', 'requerido' => true, 'max' => 80],
            'cp_desde'       => ['label' => 'Código postal desde', 'tipo' => 'numero', 'requerido' => true, 'min' => 1000, 'max' => 9999],
            'cp_hasta'       => ['label' => 'Código postal hasta', 'tipo' => 'numero', 'requerido' => true, 'min' => 1000, 'max' => 9999],
            'costo'          => ['label' => 'Costo del envío', 'tipo' => 'decimal', 'requerido' => true, 'min' => 0, 'formato' => 'dinero'],
            'permite_gratis' => ['label' => 'Los productos marcados con "Envío gratis" no pagan envío en esta zona', 'tipo' => 'bool', 'defecto' => 0],
            'dias'           => ['label' => 'Demora (texto)', 'tipo' => 'texto', 'requerido' => true, 'max' => 40, 'ayuda' => 'Ej: 2 a 4 días hábiles'],
            'prioridad'      => ['label' => 'Prioridad', 'tipo' => 'numero', 'min' => 0, 'max' => 999, 'defecto' => 0,
                                 'ayuda' => 'Si dos zonas cubren el mismo código postal, gana la de mayor prioridad.'],
        ],
        'validar'  => fn(array $d) => ((int) $d['cp_desde'] > (int) $d['cp_hasta']) ? 'El código postal "desde" es mayor que el "hasta".' : null,
    ],

    'paginas' => [
        'titulo'   => 'Páginas',
        'singular' => 'página',
        'tabla'    => 'paginas',
        'orden'    => 't.orden',
        'columnas' => ['titulo' => 'Título', 'slug' => 'Dirección', 'updated_at' => 'Última modificación'],
        'crear'    => false,
        'borrar'   => false,
        'ver'      => fn(array $r) => url('pagina.php', ['slug' => $r['slug']]),
        'campos'   => [
            'titulo'    => ['label' => 'Título', 'tipo' => 'texto', 'requerido' => true, 'max' => 120],
            'contenido' => ['label' => 'Contenido', 'tipo' => 'html', 'requerido' => true,
                            'ayuda' => 'HTML simple: <p>párrafo</p>, <h2>subtítulo</h2>, <strong>negrita</strong>, <ul><li>lista</li></ul>, <a href="...">enlace</a>. Completá lo marcado con [ ].'],
        ],
    ],

    'arrepentimientos' => [
        'titulo'   => 'Arrepentimientos',
        'singular' => 'solicitud',
        'tabla'    => 'arrepentimientos',
        'orden'    => "FIELD(t.estado, 'recibida', 'en_proceso', 'resuelta'), t.created_at DESC",
        'buscar'   => ['t.codigo', 't.nombre', 't.email', 't.dni', 't.nro_pedido'],
        'filtro'   => 'estado',
        'columnas' => ['codigo' => 'Código', 'created_at' => 'Fecha', 'nombre' => 'Nombre', 'nro_pedido' => 'Pedido', 'estado' => 'Estado'],
        'crear'    => false,
        'borrar'   => false,
        'campos'   => [
            'codigo'       => ['label' => 'Código de trámite', 'tipo' => 'lectura'],
            'created_at'   => ['label' => 'Recibida', 'tipo' => 'lectura', 'formato' => 'fechahora'],
            'nombre'       => ['label' => 'Nombre', 'tipo' => 'lectura'],
            'dni'          => ['label' => 'DNI / CUIT', 'tipo' => 'lectura'],
            'email'        => ['label' => 'Email', 'tipo' => 'lectura', 'formato' => 'email'],
            'telefono'     => ['label' => 'Teléfono', 'tipo' => 'lectura'],
            'nro_pedido'   => ['label' => 'Pedido o factura', 'tipo' => 'lectura'],
            'fecha_compra' => ['label' => 'Fecha de compra', 'tipo' => 'lectura', 'formato' => 'fecha'],
            'producto'     => ['label' => 'Producto', 'tipo' => 'lectura'],
            'motivo'       => ['label' => 'Comentarios del cliente', 'tipo' => 'lectura'],
            'estado'       => ['label' => 'Estado', 'tipo' => 'select', 'requerido' => true,
                               'opciones' => ['recibida' => 'Recibida', 'en_proceso' => 'En proceso', 'resuelta' => 'Resuelta']],
            'notas'        => ['label' => 'Notas internas', 'tipo' => 'textarea', 'ayuda' => 'Solo las ve el local.'],
        ],
    ],

    'newsletter' => [
        'titulo'   => 'Suscriptores',
        'singular' => 'suscriptor',
        'tabla'    => 'newsletter',
        'orden'    => 't.created_at DESC',
        'buscar'   => ['t.email'],
        'columnas' => ['email' => 'Email', 'created_at' => 'Fecha'],
        'crear'    => false,
        'editar'   => false,
        'exportar' => ['email' => 'Email', 'created_at' => 'Fecha'],
        'campos'   => [],
    ],
];
