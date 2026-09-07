<?php defined('APP_NOMBRE') or exit; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar · <?= e(APP_NOMBRE) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('css/estilos.css') ?>">
</head>
<body class="pantalla-login">
    <div class="tarjeta-login">
        <div class="marca-login">
            <i class="bi bi-hexagon-fill"></i>
            <h1><?= e(APP_NOMBRE) ?></h1>
            <p>Ingresa con tu cuenta para continuar</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <?= Seguridad::campo() ?>
            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="usuario" class="form-control" required autofocus
                           value="<?= e(post('usuario', '')) ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="clave" id="clave" class="form-control" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="
                        const i=document.getElementById('clave');
                        i.type = i.type==='password' ? 'text' : 'password';">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button class="btn btn-primary w-100 py-2" type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
