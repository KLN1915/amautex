<?php
/** Módulo: cierre de sesión. */
defined('APP_NOMBRE') or exit;

registrar('Cierre de sesión');
Auth::salir();
redirigir(BASE_URL . 'index.php?p=login');
