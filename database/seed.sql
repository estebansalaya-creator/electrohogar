-- =============================================================
--  Carlos Confort - Datos de ejemplo
--  Productos, precios y stock son de DEMOSTRACIÓN: reemplazar con
--  el catálogo real (ver herramientas/importar_productos.php).
-- =============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE producto_specs;
TRUNCATE producto_imagenes;
TRUNCATE productos;
TRUNCATE marcas;
TRUNCATE categorias;
TRUNCATE banners;
TRUNCATE cupones;
TRUNCATE zonas_envio;
TRUNCATE paginas;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- Categorías
-- -------------------------------------------------------------
INSERT INTO categorias (id, parent_id, nombre, slug, imagen, orden, en_menu) VALUES
 (1,  NULL, 'Electrodomésticos',          'electrodomesticos', 'productos/heladera-inox.svg',     1, 0),
 (2,  NULL, 'Climatización',              'climatizacion',     'productos/aire-blanco.svg',       2, 0),
 (3,  NULL, 'Pequeños electrodomésticos', 'pequenos-electro',  'productos/cafetera-inox.svg',     3, 0),
 (4,  NULL, 'TV y audio',                 'tv-audio',          'productos/tv-negro.svg',          4, 0),
 (5,  NULL, 'Descanso',                   'descanso',          'productos/colchon-blanco.svg',    5, 0),

 (10, 1, 'Heladeras y freezers',          'heladeras',            'productos/heladera-inox.svg',     1, 1),
 (11, 1, 'Lavarropas y secarropas',       'lavarropas',           'productos/lavarropas-blanco.svg', 2, 1),
 (12, 1, 'Cocinas y hornos',              'cocinas',              'productos/cocina-inox.svg',       3, 1),
 (13, 1, 'Termotanques y calefones',      'termotanques',         'productos/termotanque-blanco.svg',4, 1),

 (20, 2, 'Aires acondicionados',          'aires-acondicionados', 'productos/aire-blanco.svg',       1, 1),
 (21, 2, 'Ventiladores',                  'ventiladores',         'productos/ventilador-blanco.svg', 2, 0),
 (22, 2, 'Calefacción',                   'calefaccion',          'productos/calefactor-blanco.svg', 3, 0),

 (30, 3, 'Microondas',                    'microondas',           'productos/microondas-blanco.svg', 1, 1),
 (31, 3, 'Cafeteras',                     'cafeteras',            'productos/cafetera-inox.svg',     2, 0),
 (32, 3, 'Licuadoras y batidoras',        'licuadoras',           'productos/licuadora-blanco.svg',  3, 0),
 (33, 3, 'Pavas eléctricas',              'pavas',                'productos/pava-inox.svg',         4, 0),

 (40, 4, 'Smart TV',                      'smart-tv',             'productos/tv-negro.svg',          1, 1),
 (41, 4, 'Parlantes',                     'parlantes',            'productos/parlante-negro.svg',    2, 0),

 (50, 5, 'Colchones',                     'colchones',            'productos/colchon-blanco.svg',    1, 1),
 (51, 5, 'Sommiers',                      'sommiers',             'productos/sommier.svg',           2, 0);

-- -------------------------------------------------------------
-- Marcas
-- -------------------------------------------------------------
INSERT INTO marcas (id, nombre, slug) VALUES
 (1,'Samsung','samsung'), (2,'LG','lg'), (3,'Drean','drean'), (4,'Whirlpool','whirlpool'),
 (5,'Philco','philco'), (6,'BGH','bgh'), (7,'Atma','atma'), (8,'Peabody','peabody'),
 (9,'Liliana','liliana'), (10,'Gafa','gafa'), (11,'Electrolux','electrolux'), (12,'Noblex','noblex'),
 (13,'TCL','tcl'), (14,'Philips','philips'), (15,'Rheem','rheem'), (16,'Orbis','orbis'),
 (17,'Piero','piero'), (18,'Cannon','cannon'), (19,'Oster','oster'), (20,'Escorial','escorial'),
 (21,'Longvie','longvie'), (22,'JBL','jbl');

-- -------------------------------------------------------------
-- Productos (DEMO)
-- -------------------------------------------------------------
INSERT INTO productos
 (id, categoria_id, marca_id, sku, nombre, slug, descripcion, precio_lista, precio, cuotas_sin_interes, stock, envio_gratis, destacado, imagen, vistas)
VALUES
 (1, 10, 1, 'CC-HEL-001', 'Heladera No Frost 382 L inoxidable', 'heladera-no-frost-382-l-inoxidable-samsung',
  'Heladera con freezer superior y sistema No Frost: no se forma escarcha y el frío se reparte parejo en todos los estantes. Compresor inverter de bajo consumo y estantes de vidrio templado.',
  1899999, 1599999, 12, 8, 1, 1, 'productos/heladera-inox.svg', 420),
 (2, 10, 4, 'CC-HEL-002', 'Heladera No Frost 375 L blanca', 'heladera-no-frost-375-l-blanca-whirlpool',
  'Heladera familiar con freezer superior, control electrónico de temperatura y cajón de verduras con regulador de humedad.',
  1450000, 1349999, 6, 5, 1, 0, 'productos/heladera-blanco.svg', 210),
 (3, 10, 10, 'CC-HEL-003', 'Heladera cíclica con freezer 282 L blanca', 'heladera-ciclica-con-freezer-282-l-gafa',
  'Heladera de frío cíclico con freezer independiente. Ideal para departamentos y cocinas chicas.',
  0, 899999, 6, 12, 0, 0, 'productos/heladera-blanco.svg', 150),
 (4, 10, 2, 'CC-HEL-004', 'Heladera side by side 600 L negra', 'heladera-side-by-side-600-l-negra-lg',
  'Dos puertas verticales con dispenser de agua y hielo, compresor inverter lineal y 600 litros de capacidad total.',
  3799999, 3299999, 12, 2, 1, 1, 'productos/heladera-negro.svg', 380),

 (5, 11, 3, 'CC-LAV-001', 'Lavarropas automático carga frontal 8 kg 1200 rpm', 'lavarropas-carga-frontal-8-kg-drean',
  'Lavarropas de carga frontal con 18 programas, centrifugado de 1200 rpm y función de lavado rápido de 30 minutos.',
  1150000, 989999, 12, 10, 1, 1, 'productos/lavarropas-blanco.svg', 510),
 (6, 11, 1, 'CC-LAV-002', 'Lavarropas carga frontal inverter 9 kg inoxidable', 'lavarropas-carga-frontal-inverter-9-kg-samsung',
  'Motor inverter silencioso, 9 kg de capacidad y lavado al vapor para eliminar alérgenos.',
  1599999, 1399999, 12, 4, 1, 0, 'productos/lavarropas-inox.svg', 260),
 (7, 11, 3, 'CC-LAV-003', 'Lavarropas carga superior 7 kg blanco', 'lavarropas-carga-superior-7-kg-drean',
  'Lavarropas automático de carga superior, fácil de usar, con 10 programas y nivel de agua regulable.',
  0, 749999, 6, 7, 0, 0, 'productos/lavarropas-blanco.svg', 140),
 (8, 11, 11, 'CC-LAV-004', 'Secarropas por calor 7 kg', 'secarropas-por-calor-7-kg-electrolux',
  'Secarropas por calor con sensor de humedad que corta el ciclo cuando la ropa está seca.',
  0, 699999, 6, 3, 0, 0, 'productos/lavarropas-inox.svg', 90),

 (9, 12, 20, 'CC-COC-001', 'Cocina multigas 56 cm blanca', 'cocina-multigas-56-cm-blanca-escorial',
  'Cocina de 4 hornallas con horno de 70 L, válvula de seguridad y encendido a chispa. Funciona con gas natural o envasado.',
  649999, 579999, 6, 9, 0, 0, 'productos/cocina-blanco.svg', 230),
 (10, 12, 21, 'CC-COC-002', 'Cocina 4 hornallas 56 cm inoxidable', 'cocina-4-hornallas-56-cm-inoxidable-longvie',
  'Cocina de acero inoxidable con horno autolimpiante, grill y luz interior.',
  0, 899999, 6, 4, 1, 0, 'productos/cocina-inox.svg', 180),
 (11, 12, 7, 'CC-COC-003', 'Horno eléctrico 45 L con grill', 'horno-electrico-45-l-con-grill-atma',
  'Horno eléctrico de mesada con 45 L, grill, convección y timer de 120 minutos.',
  189999, 159999, 3, 15, 0, 0, 'productos/microondas-negro.svg', 160),

 (12, 13, 15, 'CC-TER-001', 'Termotanque a gas 80 L multigas', 'termotanque-a-gas-80-l-multigas-rheem',
  'Termotanque de 80 litros con recuperación rápida, termostato regulable y ánodo de magnesio.',
  0, 789999, 6, 6, 0, 0, 'productos/termotanque-blanco.svg', 200),
 (13, 13, 16, 'CC-TER-002', 'Termotanque eléctrico 50 L', 'termotanque-electrico-50-l-orbis',
  'Termotanque eléctrico de 50 litros para colgar, con indicador de temperatura y válvula de seguridad.',
  529999, 469999, 6, 8, 0, 0, 'productos/termotanque-blanco.svg', 120),
 (14, 13, 16, 'CC-TER-003', 'Calefón 14 L tiro natural', 'calefon-14-l-tiro-natural-orbis',
  'Calefón de 14 litros por minuto con encendido electrónico a pilas y control de llama.',
  0, 459999, 6, 5, 0, 0, 'productos/termotanque-blanco.svg', 70),

 (15, 20, 6, 'CC-AIR-001', 'Aire acondicionado split inverter 3000 frigorías frío/calor', 'aire-split-inverter-3000-frigorias-bgh',
  'Split inverter frío/calor de 3000 frigorías, ideal para ambientes de hasta 25 m². Bajo consumo y funcionamiento silencioso.',
  1249999, 1049999, 12, 10, 1, 1, 'productos/aire-blanco.svg', 640),
 (16, 20, 1, 'CC-AIR-002', 'Aire acondicionado split inverter 4500 frigorías frío/calor', 'aire-split-inverter-4500-frigorias-samsung',
  'Split inverter de 4500 frigorías para ambientes de hasta 40 m², con WiFi y modo de enfriamiento rápido.',
  1799999, 1599999, 12, 4, 1, 0, 'productos/aire-blanco.svg', 300),
 (17, 20, 5, 'CC-AIR-003', 'Aire acondicionado split 2600 W frío/calor', 'aire-split-2600-w-frio-calor-philco',
  'Split on/off frío/calor con control remoto, función sleep y filtro lavable.',
  0, 849999, 6, 6, 0, 0, 'productos/aire-blanco.svg', 190),

 (18, 21, 9, 'CC-VEN-001', 'Ventilador de pie 20" 3 velocidades', 'ventilador-de-pie-20-liliana',
  'Ventilador de pie con 5 aspas metálicas, altura regulable y oscilación.',
  99999, 84999, 3, 25, 0, 1, 'productos/ventilador-blanco.svg', 350),
 (19, 21, 7, 'CC-VEN-002', 'Ventilador de techo 4 palas con luz', 'ventilador-de-techo-4-palas-con-luz-atma',
  'Ventilador de techo con 4 palas de madera, luz LED y control remoto.',
  0, 159999, 3, 9, 0, 0, 'productos/ventilador-negro.svg', 110),
 (20, 21, 9, 'CC-VEN-003', 'Turbo ventilador 20" de piso', 'turbo-ventilador-20-liliana',
  'Turbo ventilador de alta potencia con inclinación regulable.',
  0, 69999, 3, 14, 0, 0, 'productos/ventilador-negro.svg', 80),

 (21, 22, 8, 'CC-CAL-001', 'Caloventor cerámico 2000 W', 'caloventor-ceramico-2000-w-peabody',
  'Caloventor con elemento cerámico, termostato regulable y protección contra vuelco.',
  79999, 64999, 3, 20, 0, 0, 'productos/calefactor-blanco.svg', 140),
 (22, 22, 9, 'CC-CAL-002', 'Panel calefactor 500 W', 'panel-calefactor-500-w-liliana',
  'Panel calefactor de bajo consumo para colgar o apoyar, con termostato.',
  0, 89999, 3, 10, 0, 0, 'productos/calefactor-blanco.svg', 60),
 (23, 22, 20, 'CC-CAL-003', 'Calefactor tiro balanceado 3000 kcal', 'calefactor-tiro-balanceado-3000-kcal-escorial',
  'Calefactor a gas de tiro balanceado con encendido piezoeléctrico y válvula de seguridad.',
  0, 389999, 6, 5, 0, 0, 'productos/calefactor-blanco.svg', 75),

 (24, 30, 6, 'CC-MIC-001', 'Microondas digital 20 L blanco', 'microondas-digital-20-l-bgh',
  'Microondas de 20 litros con 10 niveles de potencia, menús automáticos y descongelado por peso.',
  229999, 189999, 6, 14, 0, 1, 'productos/microondas-blanco.svg', 330),
 (25, 30, 1, 'CC-MIC-002', 'Microondas con grill 28 L negro', 'microondas-con-grill-28-l-samsung',
  'Microondas de 28 litros con grill, plato cerámico y función de mantener caliente.',
  0, 349999, 6, 6, 0, 0, 'productos/microondas-negro.svg', 120),

 (26, 31, 8, 'CC-CAF-001', 'Cafetera express 20 bar inoxidable', 'cafetera-express-20-bar-peabody',
  'Cafetera express con bomba de 20 bar, vaporizador para espumar leche y depósito de 1,5 L.',
  349999, 289999, 6, 7, 1, 1, 'productos/cafetera-inox.svg', 290),
 (27, 31, 19, 'CC-CAF-002', 'Cafetera de filtro 1,5 L negra', 'cafetera-de-filtro-1-5-l-oster',
  'Cafetera de filtro para 12 tazas con jarra de vidrio y función mantener caliente.',
  0, 74999, 3, 18, 0, 0, 'productos/cafetera-negro.svg', 95),

 (28, 32, 14, 'CC-LIC-001', 'Licuadora 2 L 700 W', 'licuadora-2-l-700-w-philips',
  'Licuadora con jarra de 2 litros, 5 velocidades y función pulso. Cuchillas de acero inoxidable.',
  129999, 104999, 3, 11, 0, 0, 'productos/licuadora-blanco.svg', 130),
 (29, 32, 7, 'CC-LIC-002', 'Batidora de mano 400 W', 'batidora-de-mano-400-w-atma',
  'Batidora de mano con 5 velocidades, turbo y accesorios para batir y amasar.',
  0, 49999, 3, 16, 0, 0, 'productos/licuadora-rojo.svg', 55),

 (30, 33, 14, 'CC-PAV-001', 'Pava eléctrica 1,7 L inoxidable', 'pava-electrica-1-7-l-philips',
  'Pava eléctrica de acero inoxidable con corte automático y base giratoria de 360°.',
  69999, 54999, 3, 30, 0, 1, 'productos/pava-inox.svg', 410),
 (31, 33, 7, 'CC-PAV-002', 'Pava eléctrica con control de temperatura', 'pava-electrica-control-temperatura-atma',
  'Pava con selector de temperatura para mate, té y café. Mantiene el agua caliente 30 minutos.',
  0, 79999, 3, 12, 0, 0, 'productos/pava-negro.svg', 240),

 (32, 40, 1, 'CC-TV-001', 'Smart TV 55" 4K UHD', 'smart-tv-55-4k-uhd-samsung',
  'Smart TV de 55 pulgadas con resolución 4K, HDR y apps de streaming integradas.',
  1299999, 1099999, 12, 9, 1, 1, 'productos/tv-negro.svg', 580),
 (33, 40, 13, 'CC-TV-002', 'Smart TV 43" Full HD Google TV', 'smart-tv-43-full-hd-google-tv-tcl',
  'Smart TV de 43 pulgadas con Google TV, control por voz y Chromecast integrado.',
  0, 549999, 12, 15, 1, 0, 'productos/tv-negro.svg', 260),
 (34, 40, 12, 'CC-TV-003', 'Smart TV 32" HD', 'smart-tv-32-hd-noblex',
  'Smart TV de 32 pulgadas, ideal para dormitorio o cocina.',
  399999, 329999, 6, 20, 0, 0, 'productos/tv-negro.svg', 170),

 (35, 41, 22, 'CC-PAR-001', 'Parlante bluetooth portátil resistente al agua', 'parlante-bluetooth-portatil-jbl',
  'Parlante portátil con 12 horas de batería, resistencia al agua IP67 y sonido potente.',
  239999, 199999, 6, 10, 0, 0, 'productos/parlante-negro.svg', 150),

 (36, 50, 18, 'CC-COL-001', 'Colchón de espuma 2 plazas 140 x 190', 'colchon-espuma-2-plazas-140x190-cannon',
  'Colchón de espuma de alta densidad con pillow top, firmeza media y tela de jacquard.',
  599999, 479999, 12, 6, 1, 1, 'productos/colchon-blanco.svg', 300),
 (37, 50, 17, 'CC-COL-002', 'Colchón de resortes pocket 160 x 200', 'colchon-resortes-pocket-160x200-piero',
  'Colchón queen de resortes individuales ensacados que reducen el movimiento entre las dos personas.',
  0, 999999, 12, 3, 1, 0, 'productos/colchon-blanco.svg', 140),
 (38, 50, 18, 'CC-COL-003', 'Colchón de espuma 1 plaza 80 x 190', 'colchon-espuma-1-plaza-80x190-cannon',
  'Colchón de una plaza de espuma de alta densidad, firmeza media.',
  0, 239999, 6, 12, 0, 0, 'productos/colchon-blanco.svg', 85),

 (39, 51, 17, 'CC-SOM-001', 'Sommier 2 plazas 140 x 190 con colchón', 'sommier-2-plazas-140x190-piero',
  'Conjunto de sommier con base de madera y colchón de espuma de alta densidad.',
  899999, 769999, 12, 4, 1, 0, 'productos/sommier.svg', 120);

-- Características (ejemplos)
INSERT INTO producto_specs (producto_id, nombre, valor, orden) VALUES
 (1,'Capacidad total','382 L',1), (1,'Sistema de frío','No Frost',2), (1,'Compresor','Inverter',3),
 (1,'Eficiencia energética','A',4), (1,'Medidas (alto x ancho x prof.)','178 x 60 x 68 cm',5), (1,'Color','Inoxidable',6),
 (5,'Capacidad de lavado','8 kg',1), (5,'Centrifugado','1200 rpm',2), (5,'Programas','18',3), (5,'Eficiencia energética','A',4),
 (15,'Capacidad','3000 frigorías',1), (15,'Modo','Frío / calor',2), (15,'Tecnología','Inverter',3), (15,'Superficie recomendada','Hasta 25 m²',4),
 (32,'Pantalla','55"',1), (32,'Resolución','4K UHD (3840 x 2160)',2), (32,'Conectividad','WiFi, Bluetooth, 3 HDMI, 2 USB',3),
 (36,'Medidas','140 x 190 x 26 cm',1), (36,'Material','Espuma de alta densidad',2), (36,'Firmeza','Media',3);

-- Garantía para todos los productos
INSERT INTO producto_specs (producto_id, nombre, valor, orden)
SELECT id, 'Garantía', '12 meses oficial del fabricante', 99 FROM productos;

-- -------------------------------------------------------------
-- Banners de la home
-- -------------------------------------------------------------
INSERT INTO banners (titulo, subtitulo, texto_boton, url, imagen, etiqueta_valor, etiqueta_texto, tema, orden) VALUES
 ('Equipá la cocina en 12 cuotas sin interés',
  'Heladeras, cocinas y lavarropas con envío gratis en seleccionados a CABA y GBA.',
  'Ver electrodomésticos', 'productos.php?cat=electrodomesticos', 'productos/heladera-inox.svg', '12', 'cuotas sin interés', 'azul', 1),
 ('Llegá al verano con el aire instalado',
  'Splits inverter frío/calor para ambientes de 20 a 40 m². Pagando por transferencia, 10% menos.',
  'Ver aires acondicionados', 'productos.php?cat=aires-acondicionados', 'productos/aire-blanco.svg', '10%', 'menos con transferencia', 'naranja', 2),
 ('Dormí mejor desde esta noche',
  'Colchones y sommiers con envío gratis a CABA y GBA, en hasta 12 cuotas sin interés.',
  'Ver colchones', 'productos.php?cat=descanso', 'productos/colchon-blanco.svg', 'Gratis', 'envío a CABA y GBA', 'claro', 3);

-- -------------------------------------------------------------
-- Cupones (DEMO)
-- -------------------------------------------------------------
INSERT INTO cupones (codigo, tipo, valor, minimo_compra, desde, hasta) VALUES
 ('BIENVENIDA',  'porcentaje', 5,     0,      NULL, NULL),
 ('CONFORT20MIL','monto',      20000, 300000, NULL, NULL);

-- -------------------------------------------------------------
-- Zonas de envío por código postal (ajustar costos reales)
-- -------------------------------------------------------------
INSERT INTO zonas_envio (nombre, cp_desde, cp_hasta, costo, permite_gratis, dias, prioridad) VALUES
 ('CABA',               1000, 1499, 9900,  1, '24 a 72 h hábiles',   10),
 ('Gran Buenos Aires',  1600, 1999, 14900, 1, '2 a 4 días hábiles',  10),
 ('Resto del país',     2000, 9499, 29900, 0, '4 a 8 días hábiles',   0);

-- -------------------------------------------------------------
-- Páginas institucionales (completar los textos marcados con [ ])
-- -------------------------------------------------------------
INSERT INTO paginas (slug, titulo, contenido, orden) VALUES
('quienes-somos', 'Quiénes somos',
 '<figure class="pagina__foto"><img src="assets/img/marca/local.jpg" alt="Frente del local de Carlos Confort" width="947" height="957"></figure><p><strong>Carlos Confort, la mejor elección para su hogar.</strong> Somos una casa de electrodomésticos, colchones y artículos para el hogar. [Completar con la historia del negocio, años de trayectoria y zona de atención.]</p><p>Vendemos con garantía oficial, factura y envío a todo el país.</p>', 1),
('como-comprar', 'Cómo comprar',
 '<ol><li>Buscá el producto o recorré las categorías.</li><li>Tocá <strong>Agregar al carrito</strong>. Podés seguir comprando o ir al carrito.</li><li>En el carrito ingresá tu código postal para ver el costo de envío y, si tenés, tu cupón de descuento.</li><li>Tocá <strong>Enviar pedido por WhatsApp</strong>: te llega el detalle listo para mandar y te confirmamos stock, forma de pago y entrega.</li></ol><p>Pagando por transferencia o depósito bancario tenés 10% de descuento.</p>', 2),
('envios', 'Envíos',
 '<p>Enviamos a todo el país. En CABA y GBA el envío es gratis en los productos marcados con <strong>Envío gratis</strong>.</p><p>El costo y el plazo dependen de tu código postal: podés calcularlo en cada producto y en el carrito. También podés retirar sin cargo por nuestro local [dirección y horario].</p>', 3),
('cambios-y-devoluciones', 'Cambios y devoluciones',
 '<p>Tenés 10 días corridos desde que recibís el producto para arrepentirte de la compra, sin dar explicaciones. Podés iniciar el trámite desde el <a href="arrepentimiento.php">Botón de arrepentimiento</a>.</p><p>Para cambios, el producto tiene que estar sin uso, con su embalaje original y accesorios. [Completar condiciones de cambio y garantía.]</p>', 4),
('preguntas-frecuentes', 'Preguntas frecuentes',
 '<h2>¿Los precios incluyen IVA?</h2><p>Sí, todos los precios publicados son finales e incluyen IVA. Debajo de cada precio se informa el precio sin impuestos nacionales.</p><h2>¿Puedo pagar en cuotas?</h2><p>Sí, cada producto indica cuántas cuotas sin interés tiene disponibles.</p><h2>¿Hacen factura A?</h2><p>[Completar.]</p><h2>¿Instalan los aires acondicionados?</h2><p>[Completar.]</p>', 5),
('terminos-y-condiciones', 'Términos y condiciones',
 '<p>[Completar con los términos y condiciones de venta. Recomendamos que los revise un profesional.]</p>', 6),
('politica-de-privacidad', 'Política de privacidad',
 '<p>Los datos que nos dejás (por ejemplo, tu email para el newsletter o los datos del formulario de arrepentimiento) se usan solo para responderte y gestionar tu pedido, según la Ley 25.326 de Protección de Datos Personales. [Completar.]</p><p>Usamos cookies propias para que funcione el carrito de compras.</p>', 7),
('contacto', 'Contacto',
 '<p>Escribinos por WhatsApp o por email y te respondemos en el día hábil.</p>', 8);
