-- ============================================================
--  Amautex · esquema base (SISTEMA MULTI-ACADEMIA)
--
--  El sistema se alquila a varias academias. Toda la información
--  operativa lleva `academia_id`: ese campo es la frontera entre
--  una academia y otra. Solo el superadmin (academia_id = NULL)
--  ve y administra todas.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `amautex`
    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `amautex`;

-- ------------------------------------------------------------
--  Academias (los clientes que alquilan el sistema)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `academias` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre`          VARCHAR(120) NOT NULL,
    `codigo`          VARCHAR(30)  NOT NULL COMMENT 'identificador corto: demo, sanmarcos…',
    `ruc`             VARCHAR(20)  DEFAULT NULL,
    `correo`          VARCHAR(120) DEFAULT NULL,
    `telefono`        VARCHAR(30)  DEFAULT NULL,
    `direccion`       VARCHAR(180) DEFAULT NULL,
    `color`           VARCHAR(9)   NOT NULL DEFAULT '#2563eb' COMMENT 'color de marca en el panel',
    `logo`            VARCHAR(180) DEFAULT NULL,
    `plan`            ENUM('prueba','basico','pro') NOT NULL DEFAULT 'prueba',
    `estado`          ENUM('activa','suspendida') NOT NULL DEFAULT 'activa',
    `tarifa_estatal`    DECIMAL(8,2) NOT NULL DEFAULT 12.00 COMMENT 'cuota del alumno de colegio estatal',
    `tarifa_particular` DECIMAL(8,2) NOT NULL DEFAULT 14.00 COMMENT 'cuota del alumno de colegio particular',
    `tarifa_libre`      DECIMAL(8,2) NOT NULL DEFAULT 14.00 COMMENT 'cuota del alumno libre',
    `limite_usuarios` SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    `limite_alumnos`  INT UNSIGNED NOT NULL DEFAULT 200,
    `inicio_plan`     DATE DEFAULT NULL,
    `fin_plan`        DATE DEFAULT NULL COMMENT 'vencimiento del alquiler; NULL = sin vencimiento',
    `creado_en`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_codigo` (`codigo`),
    KEY `idx_estado` (`estado`),
    KEY `idx_fin_plan` (`fin_plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Usuarios
--  academia_id NULL  -> superadmin (dueño del sistema)
--  academia_id > 0   -> usuario que solo ve su academia
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `academia_id`   INT UNSIGNED DEFAULT NULL,
    `nombre`        VARCHAR(100) NOT NULL,
    `usuario`       VARCHAR(50)  NOT NULL,
    `correo`        VARCHAR(120) DEFAULT NULL,
    `clave`         VARCHAR(255) NOT NULL,
    `rol`           ENUM('superadmin','admin','operador','consulta') NOT NULL DEFAULT 'consulta',
    `activo`        TINYINT(1)   NOT NULL DEFAULT 1,
    `debe_cambiar_clave` TINYINT(1) NOT NULL DEFAULT 0,
    `ultimo_acceso` DATETIME     DEFAULT NULL,
    `creado_en`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_usuario` (`usuario`) COMMENT 'el login es único en todo el sistema',
    KEY `idx_academia` (`academia_id`),
    KEY `idx_activo` (`activo`),
    CONSTRAINT `fk_usuario_academia` FOREIGN KEY (`academia_id`)
        REFERENCES `academias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Colegios (catálogo por academia, alimenta el combobox)
--  El tipo del colegio define la cuota que paga el alumno.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `colegios` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `academia_id` INT UNSIGNED NOT NULL,
    `nombre`      VARCHAR(150) NOT NULL,
    `tipo`        ENUM('estatal','particular') NOT NULL DEFAULT 'estatal',
    `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
    `creado_en`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_colegio_nombre` (`academia_id`, `nombre`),
    KEY `idx_colegio_tipo` (`academia_id`, `tipo`),
    CONSTRAINT `fk_colegio_academia` FOREIGN KEY (`academia_id`)
        REFERENCES `academias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Alumnos (tabla operativa: SIEMPRE lleva academia_id)
--  Todo módulo nuevo se modela igual que esta tabla.
--
--  El código es correlativo POR ACADEMIA: dos academias distintas
--  pueden tener su alumno "A0001" sin pisarse.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alumnos` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `academia_id` INT UNSIGNED NOT NULL,
    `codigo`      VARCHAR(20)  NOT NULL,
    `apellido_paterno` VARCHAR(60) NOT NULL,
    `apellido_materno` VARCHAR(60) DEFAULT NULL,
    `nombres`     VARCHAR(100) NOT NULL,
    `tipo_alumno` ENUM('estatal','particular','libre') NOT NULL DEFAULT 'estatal'
                  COMMENT 'define la cuota; libre = no viene de un colegio',
    `colegio_id`  INT UNSIGNED DEFAULT NULL COMMENT 'NULL cuando el alumno es libre',
    `nivel`       ENUM('inicial','primaria','secundaria','otro') NOT NULL DEFAULT 'secundaria',
    `grado`       TINYINT UNSIGNED DEFAULT NULL COMMENT 'inicial 3-5 anios | primaria 1-6 | secundaria 1-5',
    `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
    `creado_en`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_alumno_codigo` (`academia_id`, `codigo`),
    KEY `idx_alumno_academia` (`academia_id`),
    KEY `idx_alumno_apellidos` (`academia_id`, `apellido_paterno`, `apellido_materno`),
    KEY `idx_alumno_nivel` (`academia_id`, `nivel`, `grado`),
    KEY `idx_alumno_tipo` (`academia_id`, `tipo_alumno`),
    KEY `idx_alumno_colegio` (`colegio_id`),
    CONSTRAINT `fk_alumno_academia` FOREIGN KEY (`academia_id`)
        REFERENCES `academias` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_alumno_colegio` FOREIGN KEY (`colegio_id`)
        REFERENCES `colegios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Bitácora de acciones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bitacora` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `academia_id` INT UNSIGNED DEFAULT NULL,
    `usuario_id`  INT UNSIGNED DEFAULT NULL,
    `modulo`      VARCHAR(50)  NOT NULL,
    `accion`      VARCHAR(50)  NOT NULL,
    `detalle`     TEXT,
    `ip`          VARCHAR(45),
    `creado_en`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_bit_academia` (`academia_id`),
    KEY `idx_bit_usuario` (`usuario_id`),
    KEY `idx_bit_fecha` (`creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
