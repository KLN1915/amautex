-- ============================================================
--  Amautex · base de datos completa (esquema + datos iniciales)
--
--  UN SOLO archivo: crea la base `amautex`, todas las tablas y
--  los accesos iniciales. No hace falta ejecutar 01..06.
--
--  Importar con phpMyAdmin, HeidiSQL o por consola:
--      mysql -u root -p < 00_base_completa.sql
--
--  Accesos que quedan creados (CAMBIAR AL PRIMER INGRESO):
--      super / super123   -> dueño del sistema, ve todas las academias
--      admin / admin123   -> administrador de la "Academia Demo"
--
--  ATENCIÓN: empieza con DROP DATABASE, así que borra cualquier
--  base `amautex` que ya exista en ese servidor.
-- ============================================================


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

/*!40000 DROP DATABASE IF EXISTS `amautex`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `amautex` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `amautex`;
DROP TABLE IF EXISTS `academias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academias` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'identificador corto: demo, sanmarcosÔÇª',
  `ruc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#2563eb' COMMENT 'color de marca en el panel',
  `logo` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plan` enum('prueba','basico','pro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'prueba',
  `tarifa_estatal` decimal(8,2) NOT NULL DEFAULT '12.00',
  `tarifa_particular` decimal(8,2) NOT NULL DEFAULT '14.00',
  `tarifa_libre` decimal(8,2) NOT NULL DEFAULT '14.00',
  `puntaje_correcta`   DECIMAL(6,2) NOT NULL DEFAULT 10.00 COMMENT 'puntos por respuesta correcta',
  `puntaje_incorrecta` DECIMAL(6,2) NOT NULL DEFAULT -2.00 COMMENT 'puntos por incorrecta (o doble marca)',
  `puntaje_blanco`     DECIMAL(6,2) NOT NULL DEFAULT 0.00 COMMENT 'puntos por pregunta en blanco',
  `estado` enum('activa','suspendida') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `limite_usuarios` smallint unsigned NOT NULL DEFAULT '10',
  `limite_alumnos` int unsigned NOT NULL DEFAULT '200',
  `inicio_plan` date DEFAULT NULL,
  `fin_plan` date DEFAULT NULL COMMENT 'vencimiento del alquiler; NULL = sin vencimiento',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigo` (`codigo`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fin_plan` (`fin_plan`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `academias` WRITE;
/*!40000 ALTER TABLE `academias` DISABLE KEYS */;
INSERT INTO `academias` VALUES (1,'Academia Demo','demo',NULL,'contacto@demo.pe','999999999',NULL,'#2563eb',NULL,'prueba',12.00,14.00,14.00,'activa',10,200,'2026-09-04','2026-10-04','2026-09-04 12:05:07');
/*!40000 ALTER TABLE `academias` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `alumnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alumnos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `academia_id` int unsigned NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido_paterno` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido_materno` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombres` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_alumno` enum('estatal','particular','libre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'estatal',
  `colegio_id` int unsigned DEFAULT NULL,
  `nivel` enum('inicial','primaria','secundaria','otro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'secundaria',
  `grado` tinyint unsigned DEFAULT NULL COMMENT 'inicial 3-5 anios | primaria 1-6 | secundaria 1-5',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_alumno_codigo` (`academia_id`,`codigo`),
  KEY `idx_alumno_academia` (`academia_id`),
  KEY `idx_alumno_nivel` (`academia_id`,`nivel`,`grado`),
  KEY `idx_alumno_apellidos` (`academia_id`,`apellido_paterno`,`apellido_materno`),
  KEY `idx_alumno_tipo` (`academia_id`,`tipo_alumno`),
  KEY `idx_alumno_colegio` (`colegio_id`),
  CONSTRAINT `fk_alumno_academia` FOREIGN KEY (`academia_id`) REFERENCES `academias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_alumno_colegio` FOREIGN KEY (`colegio_id`) REFERENCES `colegios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `alumnos` WRITE;
/*!40000 ALTER TABLE `alumnos` DISABLE KEYS */;
/*!40000 ALTER TABLE `alumnos` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `bitacora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bitacora` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academia_id` int unsigned DEFAULT NULL,
  `usuario_id` int unsigned DEFAULT NULL,
  `modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accion` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalle` text COLLATE utf8mb4_unicode_ci,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bit_academia` (`academia_id`),
  KEY `idx_bit_usuario` (`usuario_id`),
  KEY `idx_bit_fecha` (`creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `bitacora` WRITE;
/*!40000 ALTER TABLE `bitacora` DISABLE KEYS */;
/*!40000 ALTER TABLE `bitacora` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `colegios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colegios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `academia_id` int unsigned NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('estatal','particular') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'estatal',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_colegio_nombre` (`academia_id`,`nombre`),
  KEY `idx_colegio_tipo` (`academia_id`,`tipo`),
  CONSTRAINT `fk_colegio_academia` FOREIGN KEY (`academia_id`) REFERENCES `academias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `colegios` WRITE;
/*!40000 ALTER TABLE `colegios` DISABLE KEYS */;
/*!40000 ALTER TABLE `colegios` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `academia_id` int unsigned DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clave` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('superadmin','admin','operador','consulta') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'consulta',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `debe_cambiar_clave` tinyint(1) NOT NULL DEFAULT '0',
  `ultimo_acceso` datetime DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario` (`usuario`) COMMENT 'el login es ├║nico en todo el sistema',
  KEY `idx_academia` (`academia_id`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_usuario_academia` FOREIGN KEY (`academia_id`) REFERENCES `academias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,NULL,'Superadministrador','super',NULL,'$2y$10$xBEm7Q2tNZz2jh5bEAH7v.kgY4/xDpaOKXda.8j5Z8XriDvukUSnK','superadmin',1,0,NULL,'2026-09-04 12:05:07'),(2,1,'Administrador Demo','admin','admin@demo.pe','$2y$10$bCfhJGBa3IllOxVwwfz9OOqcYVMeHIAE.UKGBHYn2TznuyzAnDXR.','admin',1,0,NULL,'2026-09-04 12:05:07');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- ------------------------------------------------------------
--  Ajustes del sistema (solo superadmin). Ver 07_configuracion.sql
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
--  Exámenes con fichas ópticas. Ver 08_examenes.sql
-- ------------------------------------------------------------
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
