<?php defined('APP_NOMBRE') or exit; ?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? '') ?> · <?= e(APP_NOMBRE) ?></title>
    <meta name="csrf-token" content="<?= Seguridad::token() ?>">
    <meta name="base-url" content="<?= e(BASE_URL) ?>">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <?php /* Hojas extra que pide el módulo. Las manda el controlador en $datos,
             porque el header se arma antes que la vista. */ ?>
    <?php if (!empty($estilos)) foreach ((array) $estilos as $css): ?>
    <link rel="stylesheet" href="<?= e($css) ?>">
    <?php endforeach; ?>

    <link rel="stylesheet" href="<?= asset('css/estilos.css') ?>">

    <!-- Color de marca de la academia en la que se está trabajando -->
    <style>:root { --marca: <?= e(Contexto::color()) ?>; }</style>
</head>
<body>
<div class="app">
