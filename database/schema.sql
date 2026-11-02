-- =============================================================
--  Electrohogar - Tienda online
--  Esquema de base de datos (MySQL 8 / MariaDB 10.6+)
--  Una base por cliente: este script no crea ni elige la base,
--  se corre sobre la base del cliente que se indique.
-- =============================================================
--  Uso (ejemplo con la base "electrohogar" de Carlos Confort):
--    mysql -u root -p -e "CREATE DATABASE electrohogar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
--    mysql -u root -p electrohogar < database/schema.sql
--    mysql -u root -p electrohogar < database/seed.sql
--  ATENCIÓN: borra y vuelve a crear las tablas de la base indicada.
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS producto_specs;
DROP TABLE IF EXISTS producto_imagenes;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS marcas;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS banners;
DROP TABLE IF EXISTS cupones;
DROP TABLE IF EXISTS zonas_envio;
DROP TABLE IF EXISTS paginas;
DROP TABLE IF EXISTS newsletter;
DROP TABLE IF EXISTS arrepentimientos;
DROP TABLE IF EXISTS admin_usuarios;
DROP TABLE IF EXISTS admin_intentos;
DROP TABLE IF EXISTS admin_registro;
DROP TABLE IF EXISTS ajustes;
DROP TABLE IF EXISTS pedido_eventos;
DROP TABLE IF EXISTS pedido_items;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS correos;

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- Categorías (árbol de 2 niveles: rubro > categoría)
-- -------------------------------------------------------------
CREATE TABLE categorias (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id   INT UNSIGNED NULL,
  nombre      VARCHAR(80)  NOT NULL,
  slug        VARCHAR(90)  NOT NULL,
  imagen      VARCHAR(190) NULL COMMENT 'Ilustración para accesos rápidos',
  orden       SMALLINT     NOT NULL DEFAULT 0,
  en_menu     TINYINT(1)   NOT NULL DEFAULT 1 COMMENT 'Aparece en la barra de accesos rápidos',
  activo      TINYINT(1)   NOT NULL DEFAULT 1,
  UNIQUE KEY uk_categorias_slug (slug),
  KEY ix_categorias_parent (parent_id),
  CONSTRAINT fk_categorias_parent FOREIGN KEY (parent_id) REFERENCES categorias (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Marcas
-- -------------------------------------------------------------
CREATE TABLE marcas (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre  VARCHAR(80) NOT NULL,
  slug    VARCHAR(90) NOT NULL,
  UNIQUE KEY uk_marcas_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Productos
--   precio_lista : precio de referencia (se muestra tachado si es mayor a precio)
--   precio       : precio final de venta, IVA incluido
--   iva          : alícuota para calcular "Precio sin impuestos nacionales"
--   cuotas_sin_interes : 0 = sin plan de cuotas sin interés
-- -------------------------------------------------------------
CREATE TABLE productos (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id        INT UNSIGNED  NOT NULL,
  marca_id            INT UNSIGNED  NULL,
  sku                 VARCHAR(40)   NOT NULL,
  nombre              VARCHAR(160)  NOT NULL,
  slug                VARCHAR(180)  NOT NULL,
  descripcion         TEXT          NULL,
  precio_lista        DECIMAL(12,2) NOT NULL DEFAULT 0,
  precio              DECIMAL(12,2) NOT NULL,
  iva                 DECIMAL(5,2)  NOT NULL DEFAULT 21.00,
  cuotas_sin_interes  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  stock               INT           NOT NULL DEFAULT 0,
  envio_gratis        TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Gratis en zonas que lo permiten',
  destacado           TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Aparece en "Lo más buscado"',
  imagen              VARCHAR(190)  NULL,
  vistas              INT UNSIGNED  NOT NULL DEFAULT 0,
  activo              TINYINT(1)    NOT NULL DEFAULT 1,
  created_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_productos_slug (slug),
  UNIQUE KEY uk_productos_sku (sku),
  KEY ix_productos_categoria (categoria_id, activo),
  KEY ix_productos_marca (marca_id),
  KEY ix_productos_destacado (destacado, activo),
  CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id)
    ON UPDATE CASCADE,
  CONSTRAINT fk_productos_marca FOREIGN KEY (marca_id) REFERENCES marcas (id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT ck_productos_precio CHECK (precio >= 0 AND precio_lista >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE producto_imagenes (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id  INT UNSIGNED NOT NULL,
  ruta         VARCHAR(190) NOT NULL,
  orden        SMALLINT     NOT NULL DEFAULT 0,
  KEY ix_imagenes_producto (producto_id, orden),
  CONSTRAINT fk_imagenes_producto FOREIGN KEY (producto_id) REFERENCES productos (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE producto_specs (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id  INT UNSIGNED NOT NULL,
  nombre       VARCHAR(80)  NOT NULL,
  valor        VARCHAR(190) NOT NULL,
  orden        SMALLINT     NOT NULL DEFAULT 0,
  KEY ix_specs_producto (producto_id, orden),
  CONSTRAINT fk_specs_producto FOREIGN KEY (producto_id) REFERENCES productos (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Banners del carrusel de la home
-- -------------------------------------------------------------
CREATE TABLE banners (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo       VARCHAR(120) NOT NULL,
  subtitulo    VARCHAR(220) NULL,
  texto_boton  VARCHAR(40)  NULL,
  url          VARCHAR(255) NULL,
  imagen       VARCHAR(190) NULL,
  etiqueta_valor VARCHAR(12) NULL COMMENT 'Texto grande de la etiqueta colgante: 12, 10%, Gratis',
  etiqueta_texto VARCHAR(40) NULL COMMENT 'Texto chico: cuotas sin interés',
  tema         ENUM('azul','naranja','claro') NOT NULL DEFAULT 'azul',
  orden        SMALLINT     NOT NULL DEFAULT 0,
  desde        DATE         NULL,
  hasta        DATE         NULL,
  activo       TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Cupones de descuento
-- -------------------------------------------------------------
CREATE TABLE cupones (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo         VARCHAR(30)   NOT NULL,
  tipo           ENUM('porcentaje','monto') NOT NULL DEFAULT 'porcentaje',
  valor          DECIMAL(12,2) NOT NULL,
  minimo_compra  DECIMAL(12,2) NOT NULL DEFAULT 0,
  desde          DATE          NULL,
  hasta          DATE          NULL,
  activo         TINYINT(1)    NOT NULL DEFAULT 1,
  UNIQUE KEY uk_cupones_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Zonas de envío por rango de código postal (CP numérico de 4 dígitos)
-- -------------------------------------------------------------
CREATE TABLE zonas_envio (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(80)   NOT NULL,
  cp_desde        SMALLINT UNSIGNED NOT NULL,
  cp_hasta        SMALLINT UNSIGNED NOT NULL,
  costo           DECIMAL(12,2) NOT NULL,
  permite_gratis  TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Aplica envío gratis de productos marcados',
  dias            VARCHAR(40)   NOT NULL COMMENT 'Texto: 2 a 4 días hábiles',
  prioridad       SMALLINT      NOT NULL DEFAULT 0 COMMENT 'Mayor prioridad gana si se superponen rangos',
  KEY ix_zonas_cp (cp_desde, cp_hasta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Páginas institucionales (contenido HTML editable)
-- -------------------------------------------------------------
CREATE TABLE paginas (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug       VARCHAR(80)  NOT NULL,
  titulo     VARCHAR(120) NOT NULL,
  contenido  MEDIUMTEXT   NOT NULL,
  orden      SMALLINT     NOT NULL DEFAULT 0,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_paginas_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Suscriptores al newsletter
-- -------------------------------------------------------------
CREATE TABLE newsletter (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email       VARCHAR(190) NOT NULL,
  ip          VARCHAR(45)  NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_newsletter_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Solicitudes del Botón de arrepentimiento (Res. SCI 424/2020)
-- -------------------------------------------------------------
CREATE TABLE arrepentimientos (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo        VARCHAR(20)  NULL,
  nombre        VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  telefono      VARCHAR(40)  NULL,
  dni           VARCHAR(15)  NOT NULL,
  nro_pedido    VARCHAR(40)  NOT NULL,
  fecha_compra  DATE         NULL,
  producto      VARCHAR(190) NULL,
  motivo        TEXT         NULL,
  estado        ENUM('recibida','en_proceso','resuelta') NOT NULL DEFAULT 'recibida',
  notas         TEXT         NULL COMMENT 'Notas internas del local',
  ip            VARCHAR(45)  NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_arrepentimientos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Panel de administración
-- -------------------------------------------------------------
CREATE TABLE admin_usuarios (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(80)  NOT NULL,
  email          VARCHAR(190) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  activo         TINYINT(1)   NOT NULL DEFAULT 1,
  principal      TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Único que puede cambiar las credenciales de cobro',
  ultimo_acceso  DATETIME     NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_intentos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip          VARCHAR(45)  NOT NULL,
  email       VARCHAR(190) NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_intentos_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cambios sensibles (credenciales de Mercado Pago, casilla de correo)
CREATE TABLE admin_registro (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED NULL,
  usuario     VARCHAR(80)  NOT NULL,
  accion      VARCHAR(60)  NOT NULL,
  detalle     VARCHAR(255) NULL,
  ip          VARCHAR(45)  NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_registro_accion (accion, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de la tienda editables desde el panel (pisan a app/config.php)
CREATE TABLE ajustes (
  clave       VARCHAR(60) NOT NULL PRIMARY KEY,
  valor       TEXT        NULL,
  updated_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Pedidos y bandeja de correos
-- -------------------------------------------------------------
CREATE TABLE pedidos (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token                CHAR(32)      NOT NULL COMMENT 'Para la página de confirmación del cliente',
  estado               ENUM('nuevo','confirmado','preparando','enviado','entregado','cancelado') NOT NULL DEFAULT 'nuevo',
  canal                VARCHAR(20)   NOT NULL DEFAULT 'whatsapp',
  nombre               VARCHAR(120)  NOT NULL,
  telefono             VARCHAR(40)   NOT NULL,
  email                VARCHAR(190)  NULL,
  entrega              ENUM('envio','retiro') NOT NULL DEFAULT 'envio',
  direccion            VARCHAR(255)  NULL,
  localidad            VARCHAR(120)  NULL,
  cp                   SMALLINT UNSIGNED NULL,
  zona                 VARCHAR(80)   NULL,
  comentarios          TEXT          NULL,
  subtotal             DECIMAL(12,2) NOT NULL,
  cupon                VARCHAR(30)   NULL,
  descuento            DECIMAL(12,2) NOT NULL DEFAULT 0,
  costo_envio          DECIMAL(12,2) NULL COMMENT 'NULL = a cotizar',
  total                DECIMAL(12,2) NOT NULL,
  total_transferencia  DECIMAL(12,2) NOT NULL,
  cuotas               TINYINT UNSIGNED NOT NULL DEFAULT 0,
  stock_descontado     TINYINT(1)    NOT NULL DEFAULT 0,
  notas_internas       TEXT          NULL,
  ip                   VARCHAR(45)   NULL,
  created_at           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME      NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_pedidos_token (token),
  KEY ix_pedidos_estado (estado, created_at),
  KEY ix_pedidos_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedido_items (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id        INT UNSIGNED  NOT NULL,
  producto_id      INT UNSIGNED  NULL,
  sku              VARCHAR(40)   NOT NULL,
  nombre           VARCHAR(160)  NOT NULL,
  marca            VARCHAR(80)   NULL,
  precio_unitario  DECIMAL(12,2) NOT NULL,
  cantidad         INT UNSIGNED  NOT NULL,
  subtotal         DECIMAL(12,2) NOT NULL,
  KEY ix_items_pedido (pedido_id),
  CONSTRAINT fk_items_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE,
  CONSTRAINT fk_items_producto FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedido_eventos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id   INT UNSIGNED NOT NULL,
  estado      VARCHAR(20)  NULL,
  nota        TEXT         NULL,
  usuario     VARCHAR(80)  NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_eventos_pedido (pedido_id, created_at),
  CONSTRAINT fk_eventos_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE correos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo        VARCHAR(40)  NOT NULL,
  referencia  VARCHAR(40)  NULL COMMENT 'Número de pedido o código de trámite',
  para        VARCHAR(190) NOT NULL,
  asunto      VARCHAR(190) NOT NULL,
  html        MEDIUMTEXT   NOT NULL,
  texto       MEDIUMTEXT   NOT NULL,
  estado      ENUM('pendiente','enviado','error','sin_configurar') NOT NULL DEFAULT 'pendiente',
  intentos    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  error       TEXT         NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  enviado_at  DATETIME     NULL,
  KEY ix_correos_estado (estado, created_at),
  KEY ix_correos_referencia (referencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
