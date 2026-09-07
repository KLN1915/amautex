<?php defined('APP_NOMBRE') or exit; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambiar contraseña · <?= e(APP_NOMBRE) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('css/estilos.css') ?>">
</head>
<body class="pantalla-login">
    <div class="tarjeta-login">
        <div class="marca-login">
            <i class="bi bi-shield-lock"></i>
            <h1>Cambiar contraseña</h1>
            <p><?= $obligatorio
                ? 'Tu contraseña fue asignada por el administrador. Define una propia para continuar.'
                : 'Actualiza tu contraseña de acceso.' ?></p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <?= Seguridad::campo() ?>
            <div class="mb-3">
                <label class="form-label">Contraseña actual</label>
                <input type="password" name="actual" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Nueva contraseña</label>
                <input type="password" name="nueva" class="form-control" required minlength="6">
            </div>
            <div class="mb-4">
                <label class="form-label">Repetir nueva contraseña</label>
                <input type="password" name="repetir" class="form-control" required minlength="6">
            </div>
            <button class="btn btn-primary w-100 py-2" type="submit">Guardar contraseña</button>
            <?php if (!$obligatorio): ?>
                <a class="btn btn-link w-100 mt-2" href="<?= modulo('dashboard') ?>">Volver al panel</a>
            <?php else: ?>
                <a class="btn btn-link w-100 mt-2" href="<?= modulo('salir') ?>">Salir</a>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
