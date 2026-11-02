-- =============================================================
--  Migración 002: registro de pedidos y bandeja de correos
--  Correr UNA vez sobre la base del cliente (si fue creada antes de esta versión):
--    mysql -u root -p electrohogar < database/migraciones/002_pedidos_correos.sql
-- =============================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS pedidos (
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

CREATE TABLE IF NOT EXISTS pedido_items (
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

CREATE TABLE IF NOT EXISTS pedido_eventos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id   INT UNSIGNED NOT NULL,
  estado      VARCHAR(20)  NULL,
  nota        TEXT         NULL,
  usuario     VARCHAR(80)  NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_eventos_pedido (pedido_id, created_at),
  CONSTRAINT fk_eventos_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bandeja de salida: todos los correos quedan registrados (enviados o no)
CREATE TABLE IF NOT EXISTS correos (
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
