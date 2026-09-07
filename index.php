<?php
/**
 * Front controller único: toda petición entra por aquí.
 *   index.php?p=usuarios              -> vista del módulo
 *   index.php?p=usuarios&accion=listar -> endpoint JSON del mismo módulo
 */
require_once __DIR__ . '/core/bootstrap.php';

$rutas = require __DIR__ . '/config/rutas.php';
$p     = preg_replace('/[^a-z0-9_]/i', '', (string) get('p', 'dashboard'));

if (!isset($rutas[$p])) {
    Vista::error(404, 'La página solicitada no existe.');
}

$ruta = $rutas[$p];

// Control de acceso: [] = público; si no, exige sesión + rol
if (!empty($ruta['roles'])) {
    Auth::exigirRol(...$ruta['roles']);
}

// Clave asignada por el administrador: no se puede usar el sistema
// hasta reemplazarla por una propia.
if (Auth::logueado() && Auth::debeCambiarClave() && !in_array($p, ['clave', 'salir'], true)) {
    if (Vista::esAjax()) Respuesta::error('Debes cambiar tu contraseña antes de continuar.', 403);
    redirigir(modulo('clave'));
}

// CSRF en todo POST (incluye AJAX vía cabecera X-CSRF-Token)
Seguridad::verificar();

$controlador = RUTA_FUNC . '/' . $ruta['controlador'];
if (!is_file($controlador)) {
    Vista::error(500, 'Controlador no encontrado: ' . $ruta['controlador']);
}

$accion = preg_replace('/[^a-z0-9_]/i', '', (string) get('accion', ''));
require $controlador;
