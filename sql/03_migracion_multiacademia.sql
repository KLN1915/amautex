-- ============================================================
--  Migración: sistema de una sola empresa  ->  multi-academia
--  Ejecutar SOLO si ya habías importado la versión anterior de
--  01_esquema.sql. En instalación nueva no hace falta.
-- ============================================================
USE `amautex`;

-- 1) Tabla de academias (ver 01_esquema.sql para el detalle de columnas)
CREATE TABLE IF NOT EXISTS `academias` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre`          VARCHAR(120) NOT NULL,
    `codigo`          VARCHAR(30)  NOT NULL,
    `ruc`             VARCHAR(20)  DEFAULT NULL,
    `correo`          VARCHAR(120) DEFAULT NULL,
    `telefono`        VARCHAR(30)  DEFAULT NULL,
    `direccion`       VARCHAR(180) DEFAULT NULL,
    `color`           VARCHAR(9)   NOT NULL DEFAULT '#2563eb',
    `logo`            VARCHAR(180) DEFAULT NULL,
    `plan`            ENUM('prueba','basico','pro') NOT NULL DEFAULT 'prueba',
    `estado`          ENUM('activa','suspendida') NOT NULL DEFAULT 'activa',
    `limite_usuarios` SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    `limite_alumnos`  INT UNSIGNED NOT NULL DEFAULT 200,
    `inicio_plan`     DATE DEFAULT NULL,
    `fin_plan`        DATE DEFAULT NULL,
    `creado_en`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Academia inicial que se queda con los datos existentes
INSERT INTO `academias` (`nombre`, `codigo`, `estado`, `inicio_plan`)
VALUES ('Academia Principal', 'principal', 'activa', CURDATE())
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 3) Nuevas columnas en usuarios
ALTER TABLE `usuarios`
    ADD COLUMN `academia_id` INT UNSIGNED DEFAULT NULL AFTER `id`,
    ADD COLUMN `debe_cambiar_clave` TINYINT(1) NOT NULL DEFAULT 0 AFTER `activo`,
    MODIFY COLUMN `rol` ENUM('superadmin','admin','operador','consulta') NOT NULL DEFAULT 'consulta',
    ADD KEY `idx_academia` (`academia_id`);

-- 4) Los usuarios que ya existían pasan a la academia inicial
UPDATE `usuarios` u
   JOIN `academias` a ON a.codigo = 'principal'
   SET u.academia_id = a.id
 WHERE u.academia_id IS NULL;

ALTER TABLE `usuarios`
    ADD CONSTRAINT `fk_usuario_academia` FOREIGN KEY (`academia_id`)
        REFERENCES `academias` (`id`) ON DELETE CASCADE;

-- 5) Superadmin del sistema (super / super123)
INSERT INTO `usuarios` (`academia_id`, `nombre`, `usuario`, `clave`, `rol`, `activo`)
VALUES (NULL, 'Superadministrador', 'super',
        '$2y$10$xBEm7Q2tNZz2jh5bEAH7v.kgY4/xDpaOKXda.8j5Z8XriDvukUSnK',
        'superadmin', 1)
ON DUPLICATE KEY UPDATE `rol` = 'superadmin';

-- 6) Bitácora con academia
ALTER TABLE `bitacora`
    ADD COLUMN `academia_id` INT UNSIGNED DEFAULT NULL AFTER `id`,
    ADD KEY `idx_bit_academia` (`academia_id`);
