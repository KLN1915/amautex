<?php
/** Módulo: inicio de sesión. */
defined('APP_NOMBRE') or exit;

if (Auth::logueado()) {
    redirigir(modulo('dashboard'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = Seguridad::texto(post('usuario'), 50);
    $clave   = (string) post('clave');

    if ($usuario === '' || $clave === '') {
        $error = 'Ingresa usuario y contraseña.';
    } elseif (Auth::login($usuario, $clave)) {
        registrar('Inicio de sesión correcto: ' . $usuario);
        redirigir(modulo('dashboard'));
    } else {
        // Auth::$error explica el motivo real: clave errada, usuario inactivo,
        // academia suspendida o plan vencido.
        $error = Auth::$error;
        registrar('Intento fallido de login: ' . $usuario . ' (' . $error . ')', 'WARN');
        usleep(400000); // freno simple contra fuerza bruta
    }
}

Vista::limpia('login', ['error' => $error]);
