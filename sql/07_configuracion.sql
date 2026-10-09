-- ============================================================
--  Migración 07: ajustes generales del sistema
--
--  Pares clave/valor que valen para TODAS las academias y que solo
--  administra el superadmin (p. ej. el acceso al lector de fichas).
--  Los valores secretos (tokens) se guardan cifrados: `cifrado = 1`
--  y la llave vive en config/clave_app.php, fuera de la base.
-- ============================================================
USE `amautex`;

CREATE TABLE IF NOT EXISTS `configuracion` (
    `clave`           VARCHAR(60)  NOT NULL,
    `valor`           TEXT         NULL,
    `cifrado`         TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = valor cifrado con la llave de la app',
    `actualizado_por` INT UNSIGNED NULL,
    `actualizado_en`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`clave`),
    CONSTRAINT `fk_config_usuario` FOREIGN KEY (`actualizado_por`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ajustes del sistema (solo superadmin)';
