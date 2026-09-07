-- ============================================================
--  Migración: colegios (estatal/particular), alumno libre y tarifas
--
--  - Nuevo catálogo `colegios` por academia (alimenta el combobox).
--  - El alumno pasa de texto libre a `colegio_id` + `tipo_alumno`.
--  - La academia guarda cuánto cobra a cada tipo (12 / 14 soles).
--  - Se retiran los datos de contacto del alumno.
-- ============================================================
USE `amautex`;

-- 1) Tarifas de la academia
ALTER TABLE `academias`
    ADD COLUMN `tarifa_estatal`    DECIMAL(8,2) NOT NULL DEFAULT 12.00 AFTER `plan`,
    ADD COLUMN `tarifa_particular` DECIMAL(8,2) NOT NULL DEFAULT 14.00 AFTER `tarifa_estatal`,
    ADD COLUMN `tarifa_libre`      DECIMAL(8,2) NOT NULL DEFAULT 14.00 AFTER `tarifa_particular`;

-- 2) Catálogo de colegios
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

-- 3) Nuevas columnas del alumno
ALTER TABLE `alumnos`
    ADD COLUMN `tipo_alumno` ENUM('estatal','particular','libre') NOT NULL DEFAULT 'estatal' AFTER `nombres`,
    ADD COLUMN `colegio_id`  INT UNSIGNED DEFAULT NULL AFTER `tipo_alumno`;

-- 4) Las instituciones escritas a mano pasan al catálogo (quedan como estatales:
--    revisar después cuáles son particulares).
INSERT IGNORE INTO `colegios` (`academia_id`, `nombre`, `tipo`)
SELECT DISTINCT `academia_id`, TRIM(`institucion`), 'estatal'
  FROM `alumnos`
 WHERE `institucion` IS NOT NULL AND TRIM(`institucion`) <> '';

UPDATE `alumnos` a
  JOIN `colegios` c
    ON c.academia_id = a.academia_id AND c.nombre = TRIM(a.institucion)
   SET a.colegio_id = c.id;

-- El alumno sin colegio pasa a ser libre
UPDATE `alumnos` SET `tipo_alumno` = 'libre' WHERE `colegio_id` IS NULL;

-- 5) Fuera la institución en texto y los datos de contacto
ALTER TABLE `alumnos`
    DROP KEY `idx_alumno_institucion`,
    DROP KEY `uq_alumno_dni`,
    DROP COLUMN `institucion`,
    DROP COLUMN `dni`,
    DROP COLUMN `correo`,
    DROP COLUMN `telefono`,
    DROP COLUMN `apoderado`,
    ADD KEY `idx_alumno_tipo` (`academia_id`, `tipo_alumno`),
    ADD KEY `idx_alumno_colegio` (`colegio_id`),
    ADD CONSTRAINT `fk_alumno_colegio` FOREIGN KEY (`colegio_id`)
        REFERENCES `colegios` (`id`) ON DELETE SET NULL;
