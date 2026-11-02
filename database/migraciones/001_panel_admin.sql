-- =============================================================
--  Migración 001: panel de administración
--  Para bases creadas antes del panel. Correr UNA vez sobre la base del cliente:
--    mysql -u root -p electrohogar < database/migraciones/001_panel_admin.sql
--  (Las bases nuevas creadas con schema.sql ya incluyen estos cambios.)
-- =============================================================
SET NAMES utf8mb4;

-- Usuarios del panel
CREATE TABLE IF NOT EXISTS admin_usuarios (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(80)  NOT NULL,
  email          VARCHAR(190) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  activo         TINYINT(1)   NOT NULL DEFAULT 1,
  ultimo_acceso  DATETIME     NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Intentos fallidos de ingreso (para frenar ataques de fuerza bruta)
CREATE TABLE IF NOT EXISTS admin_intentos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip          VARCHAR(45)  NOT NULL,
  email       VARCHAR(190) NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_intentos_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de la tienda editables desde el panel (pisan a app/config.php)
CREATE TABLE IF NOT EXISTS ajustes (
  clave       VARCHAR(60) NOT NULL PRIMARY KEY,
  valor       TEXT        NULL,
  updated_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notas internas en las solicitudes de arrepentimiento
ALTER TABLE arrepentimientos
  ADD COLUMN notas TEXT NULL AFTER estado,
  ADD COLUMN updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
