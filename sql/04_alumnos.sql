-- ============================================================
--  Migración: campos académicos en la tabla de alumnos
--  Ejecutar SOLO si ya habías importado 01_esquema.sql antes de
--  que existieran institución / nivel / grado.
-- ============================================================
USE `amautex`;

ALTER TABLE `alumnos`
    ADD COLUMN `institucion` VARCHAR(150) DEFAULT NULL AFTER `nombres`,
    ADD COLUMN `nivel` ENUM('inicial','primaria','secundaria','otro') NOT NULL DEFAULT 'secundaria' AFTER `institucion`,
    ADD COLUMN `grado` TINYINT UNSIGNED DEFAULT NULL AFTER `nivel`,
    ADD COLUMN `apoderado` VARCHAR(120) DEFAULT NULL AFTER `telefono`;

-- Los alumnos que ya existían sin código reciben uno correlativo
UPDATE `alumnos` a
   JOIN (SELECT id, academia_id,
                ROW_NUMBER() OVER (PARTITION BY academia_id ORDER BY id) AS n
           FROM `alumnos`
          WHERE codigo IS NULL OR codigo = '') t ON t.id = a.id
   SET a.codigo = CONCAT('A', LPAD(t.n, 4, '0'))
 WHERE a.codigo IS NULL OR a.codigo = '';

ALTER TABLE `alumnos`
    MODIFY COLUMN `codigo` VARCHAR(20) NOT NULL,
    ADD UNIQUE KEY `uq_alumno_codigo` (`academia_id`, `codigo`),
    ADD KEY `idx_alumno_apellidos` (`academia_id`, `apellidos`),
    ADD KEY `idx_alumno_nivel` (`academia_id`, `nivel`, `grado`),
    ADD KEY `idx_alumno_institucion` (`academia_id`, `institucion`);
