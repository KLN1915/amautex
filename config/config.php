<?php
/**
 * Configuración global del sistema.
 * Único archivo que se toca al cambiar de servidor (local / producción).
 */

// ---------- Entorno ----------
define('APP_NOMBRE', 'Amautex');
define('APP_ENTORNO', 'local');            // local | produccion
define('APP_DEBUG',   APP_ENTORNO === 'local');

// ---------- Base de datos ----------
define('DB_HOST', 'localhost');
define('DB_NAME', 'amautex');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---------- Rutas del sistema ----------
define('RAIZ',      dirname(__DIR__));                 // .../amautex
define('RUTA_CORE', RAIZ . '/core');
define('RUTA_FUNC', RAIZ . '/funciones');
define('RUTA_VISTA', RAIZ . '/vistas');
define('RUTA_UPLOADS', RAIZ . '/uploads');
define('RUTA_LOGS',  RAIZ . '/logs');

// URL base (autodetectada; en producción se puede fijar a mano)
$dir = str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
define('BASE_URL', rtrim($dir, '/') . '/');

// ---------- Sesión ----------
define('SESION_NOMBRE', 'amautex_sid');
define('SESION_VIDA', 60 * 60 * 4);        // 4 horas

// ---------- Errores ----------
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}
ini_set('log_errors', '1');
ini_set('error_log', RUTA_LOGS . '/php-error.log');
date_default_timezone_set('America/Lima');
