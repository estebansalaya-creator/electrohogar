-- =============================================================
--  Migración 003: administrador principal y registro de cambios sensibles
--  (credenciales de Mercado Pago, casilla de correo).
--  Correr UNA vez sobre la base del cliente (si fue creada antes de esta versión):
--    mysql -u root -p electrohogar < database/migraciones/003_mercadopago.sql
-- =============================================================
SET NAMES utf8mb4;

-- Solo el administrador principal puede ver y cambiar las credenciales de cobro.
ALTER TABLE admin_usuarios
  ADD COLUMN principal TINYINT(1) NOT NULL DEFAULT 0 AFTER activo;

-- El primer usuario creado pasa a ser el principal
UPDATE admin_usuarios SET principal = 1 ORDER BY id LIMIT 1;

CREATE TABLE IF NOT EXISTS admin_registro (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED NULL,
  usuario     VARCHAR(80)  NOT NULL,
  accion      VARCHAR(60)  NOT NULL,
  detalle     VARCHAR(255) NULL,
  ip          VARCHAR(45)  NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_registro_accion (accion, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
