-- ============================================================
--  Migración: `apellidos` (un campo) -> apellido paterno + materno
--  Ejecutar si ya habías importado la tabla `alumnos` anterior.
--  Los apellidos existentes se parten por el primer espacio:
--    "QUISPE ROJAS"        -> paterno QUISPE, materno ROJAS
--    "DE LA CRUZ TORRES"   -> paterno DE, materno LA CRUZ TORRES  (revisar a mano)
-- ============================================================
USE `amautex`;

ALTER TABLE `alumnos`
    ADD COLUMN `apellido_paterno` VARCHAR(60) NOT NULL DEFAULT '' AFTER `codigo`,
    ADD COLUMN `apellido_materno` VARCHAR(60) DEFAULT NULL AFTER `apellido_paterno`;

UPDATE `alumnos`
   SET `apellido_paterno` = SUBSTRING_INDEX(`apellidos`, ' ', 1),
       `apellido_materno` = NULLIF(
            TRIM(SUBSTRING(`apellidos`, LENGTH(SUBSTRING_INDEX(`apellidos`, ' ', 1)) + 2)), '');

ALTER TABLE `alumnos`
    DROP KEY `idx_alumno_apellidos`,
    DROP COLUMN `apellidos`,
    ALTER COLUMN `apellido_paterno` DROP DEFAULT,
    ADD KEY `idx_alumno_apellidos` (`academia_id`, `apellido_paterno`, `apellido_materno`);
