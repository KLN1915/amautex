<?php
/**
 * Arranque del sistema. Todo archivo de entrada incluye SOLO este archivo.
 */
require_once __DIR__ . '/../config/config.php';

// Autoload de las clases de core/
spl_autoload_register(function (string $clase) {
    $archivo = RUTA_CORE . '/' . $clase . '.php';
    if (is_file($archivo)) require_once $archivo;
});

// Librerías de terceros (composer), si existen
if (is_file(RAIZ . '/libreria/vendor/autoload.php')) {
    require_once RAIZ . '/libreria/vendor/autoload.php';
}

require_once RUTA_CORE . '/helpers.php';

Auth::iniciarSesion();

// Carga las funciones de un módulo: cargar('usuarios') -> funciones/usuarios/usuarios.php
function cargar(string $modulo, ?string $archivo = null): void
{
    $archivo = $archivo ?? $modulo;
    $ruta = RUTA_FUNC . '/' . $modulo . '/' . $archivo . '.php';
    if (is_file($ruta)) require_once $ruta;
}
