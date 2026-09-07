<?php
/**
 * Módulo: cambio de contraseña propia.
 * Es la pantalla a la que se obliga a entrar cuando el superadmin
 * entregó o restableció una credencial (usuarios.debe_cambiar_clave = 1).
 */
defined('APP_NOMBRE') or exit;

$error      = '';
$obligatorio = (bool) Auth::usuario('debe_cambiar_clave');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual  = (string) post('actual');
    $nueva   = (string) post('nueva');
    $repetir = (string) post('repetir');

    $fila = Db::uno('SELECT clave FROM usuarios WHERE id = :id', ['id' => Auth::id()]);

    if (!$fila || !password_verify($actual, $fila['clave'])) {
        $error = 'La contraseña actual no es correcta.';
    } elseif (strlen($nueva) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } elseif ($nueva !== $repetir) {
        $error = 'Las contraseñas nuevas no coinciden.';
    } elseif ($nueva === $actual) {
        $error = 'La nueva contraseña debe ser distinta de la actual.';
    } else {
        Db::actualizar('usuarios', [
            'clave'              => password_hash($nueva, PASSWORD_DEFAULT),
            'debe_cambiar_clave' => 0,
        ], 'id = :id', ['id' => Auth::id()]);

        $_SESSION['usuario']['debe_cambiar_clave'] = 0;
        bitacora('auth', 'cambiar_clave', 'Cambio de contraseña propio');
        registrar('Contraseña cambiada por el propio usuario');
        flash('Contraseña actualizada.');
        redirigir(modulo('dashboard'));
    }
}

Vista::limpia('clave', compact('error', 'obligatorio'));
