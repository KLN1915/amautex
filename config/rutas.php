<?php
/**
 * Mapa de módulos del sistema.
 *
 *   'p'         => clave que viaja en la URL (index.php?p=usuarios)
 *   controlador => archivo dentro de funciones/ que atiende la petición
 *   roles       => roles con acceso ([] = público, sin sesión)
 *   menu        => título e ícono para el sidebar (null = no aparece en el menú)
 *   grupo       => 'sistema' (solo superadmin) | 'academia' (operación diaria)
 */
return [
    'login' => [
        'controlador' => 'auth/login.php',
        'roles'       => [],
        'menu'        => null,
    ],
    'clave' => [
        'controlador' => 'auth/clave.php',
        'roles'       => ['superadmin', 'admin', 'operador', 'consulta'],
        'menu'        => null,
    ],
    'salir' => [
        'controlador' => 'auth/salir.php',
        'roles'       => ['superadmin', 'admin', 'operador', 'consulta'],
        'menu'        => null,
    ],

    // ---------- Panel ----------
    'dashboard' => [
        'controlador' => 'dashboard/dashboard.php',
        'roles'       => ['superadmin', 'admin', 'operador', 'consulta'],
        'menu'        => ['titulo' => 'Panel', 'icono' => 'bi-speedometer2'],
        'grupo'       => 'academia',
    ],

    // ---------- Administración del sistema (solo el dueño) ----------
    'academias' => [
        'controlador' => 'academias/academias.php',
        'roles'       => ['superadmin'],
        'menu'        => ['titulo' => 'Academias', 'icono' => 'bi-building'],
        'grupo'       => 'sistema',
    ],

    // ---------- Operación de la academia ----------
    'alumnos' => [
        'controlador' => 'alumnos/alumnos.php',
        'roles'       => ['superadmin', 'admin', 'operador', 'consulta'],
        'menu'        => ['titulo' => 'Alumnos', 'icono' => 'bi-mortarboard'],
        'grupo'       => 'academia',
    ],
    'precios' => [
        'controlador' => 'precios/precios.php',
        'roles'       => ['superadmin', 'admin'],
        'menu'        => ['titulo' => 'Precios', 'icono' => 'bi-cash-coin'],
        'grupo'       => 'academia',
    ],
    'usuarios' => [
        'controlador' => 'usuarios/usuarios.php',
        'roles'       => ['superadmin', 'admin'],
        'menu'        => ['titulo' => 'Usuarios', 'icono' => 'bi-people'],
        'grupo'       => 'academia',
    ],
];
