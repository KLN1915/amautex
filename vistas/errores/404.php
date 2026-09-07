<?php defined('APP_NOMBRE') or exit; ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>404 · <?= e(APP_NOMBRE) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body class="d-flex align-items-center justify-content-center vh-100 bg-light">
<div class="text-center">
    <h1 class="display-1 fw-bold text-secondary">404</h1>
    <p class="lead">La página que buscas no existe.</p>
    <a class="btn btn-primary" href="<?= modulo('dashboard') ?>">Volver al panel</a>
</div></body></html>
