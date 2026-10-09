<?php
/**
 * Configuración global del sistema.
 *
 * Los datos propios de cada servidor (base de datos, entorno) NO se editan aquí:
 * van en config/config.local.php (copiar de config.local.php.example). Ese
 * archivo no se sube al repositorio y .htaccess lo bloquea; así este archivo es
 * idéntico en local y en producción. Sin config.local.php se usan los valores
 * de abajo (los de un Laragon recién instalado).
 */

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// ---------- Entorno ----------
define('APP_NOMBRE', 'Amautex');
defined('APP_ENTORNO') or define('APP_ENTORNO', 'local');     // local | produccion
define('APP_DEBUG',   APP_ENTORNO === 'local');

// ---------- Base de datos ----------
defined('DB_HOST') or define('DB_HOST', 'localhost');
defined('DB_NAME') or define('DB_NAME', 'amautex');
defined('DB_USER') or define('DB_USER', 'root');
defined('DB_PASS') or define('DB_PASS', '');
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

// ---------- Lector de fichas ópticas (API OMR en el VPS) ----------
// Lo normal es que el superadmin cargue el token en Administración > Lector de
// fichas (queda cifrado en la base). Esto es solo el valor de respaldo.
defined('OMR_URL')   or define('OMR_URL',   getenv('OMR_URL')   ?: 'https://omr.82-25-75-182.sslip.io');
defined('OMR_TOKEN') or define('OMR_TOKEN', getenv('OMR_TOKEN') ?: '');
defined('OMR_PLANTILLA') or define('OMR_PLANTILLA', 'concurso2026');   // hoja por defecto
