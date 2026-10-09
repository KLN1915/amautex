-- ============================================================
--  Migración 08: exámenes con fichas ópticas
--
--  examenes            un examen de la academia: nivel/grado, la hoja (plantilla
--                      del lector OMR) y su clave de respuestas.
--  examen_resultados   la ficha escaneada de UN alumno en UN examen: sus
--                      respuestas, el conteo y la foto corregida.
--
--  `respuestas` guarda un carácter por pregunta: la letra marcada, '_' en blanco
--  o '*' doble marca. Con eso las notas se recalculan si cambia la clave.
-- ============================================================
USE `amautex`;

CREATE TABLE IF NOT EXISTS `examenes` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `academia_id`    INT UNSIGNED NOT NULL,
    `nombre`         VARCHAR(120) NOT NULL,
    `nivel`          ENUM('inicial','primaria','secundaria','otro') NOT NULL,
    `grado`          TINYINT UNSIGNED DEFAULT NULL COMMENT 'NULL = todos los grados del nivel',
    `fecha`          DATE         DEFAULT NULL,
    `plantilla`      VARCHAR(40)  NOT NULL COMMENT 'hoja registrada en el lector OMR',
    `preguntas`      SMALLINT UNSIGNED NOT NULL,
    `opciones`       TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `clave`          VARCHAR(300) DEFAULT NULL COMMENT 'una letra por pregunta; "-" = anulada; NULL = sin configurar',
    `creado_por`     INT UNSIGNED DEFAULT NULL,
    `creado_en`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_examen_academia` (`academia_id`, `nivel`, `grado`),
    CONSTRAINT `fk_examen_academia` FOREIGN KEY (`academia_id`)
        REFERENCES `academias` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_examen_usuario` FOREIGN KEY (`creado_por`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `examen_resultados` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `academia_id`    INT UNSIGNED NOT NULL,
    `examen_id`      INT UNSIGNED NOT NULL,
    `alumno_id`      INT UNSIGNED NOT NULL,
    `respuestas`     VARCHAR(300) NOT NULL COMMENT 'letra | _ en blanco | * doble marca',
    `revisar`        VARCHAR(600) DEFAULT NULL COMMENT 'preguntas con marca dudosa, separadas por coma',
    `correctas`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `incorrectas`    SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'incluye las dobles marcas',
    `blancos`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `multiples`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `total`          SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'preguntas que cuentan (sin las anuladas)',
    `imagen`         VARCHAR(200) DEFAULT NULL COMMENT 'ruta relativa en uploads/ de la ficha corregida',
    `entregado_en`   DATETIME     DEFAULT NULL COMMENT 'cuándo entregó el examen (hora final); desempata el ranking',
    `escaneado_por`  INT UNSIGNED DEFAULT NULL,
    `creado_en`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_resultado_alumno` (`examen_id`, `alumno_id`) COMMENT 'una ficha por alumno y examen',
    KEY `idx_resultado_academia` (`academia_id`),
    KEY `idx_resultado_alumno` (`alumno_id`),
    CONSTRAINT `fk_resultado_academia` FOREIGN KEY (`academia_id`)
        REFERENCES `academias` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_resultado_examen` FOREIGN KEY (`examen_id`)
        REFERENCES `examenes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_resultado_alumno` FOREIGN KEY (`alumno_id`)
        REFERENCES `alumnos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_resultado_usuario` FOREIGN KEY (`escaneado_por`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Reglas de puntaje de cada academia (las usa el módulo Exámenes).
--  Puntaje = correctas*puntaje_correcta + incorrectas*puntaje_incorrecta
--            + blancos*puntaje_blanco   (la doble marca cuenta como incorrecta)
-- ------------------------------------------------------------
ALTER TABLE `academias`
    ADD COLUMN `puntaje_correcta`   DECIMAL(6,2) NOT NULL DEFAULT 10.00 AFTER `tarifa_libre`,
    ADD COLUMN `puntaje_incorrecta` DECIMAL(6,2) NOT NULL DEFAULT -2.00 AFTER `puntaje_correcta`,
    ADD COLUMN `puntaje_blanco`     DECIMAL(6,2) NOT NULL DEFAULT 0.00  AFTER `puntaje_incorrecta`;
