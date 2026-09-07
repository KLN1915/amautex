<?php
defined('APP_NOMBRE') or exit;

$menu      = require RAIZ . '/config/rutas.php';
$listaAcad = Contexto::listaAcademias();   // vacío si no es superadmin
?>
<aside class="barra-lateral" id="barraLateral">
    <div class="marca">
        <i class="bi bi-hexagon-fill"></i>
        <span><?= e(APP_NOMBRE) ?></span>
    </div>

    <?php if (es_super()): ?>
        <!-- Selector de academia: solo el dueño del sistema lo ve -->
        <div class="selector-academia">
            <label class="form-label">Academia</label>
            <select class="form-select form-select-sm" id="selectorAcademia">
                <option value="">— Todas (vista global) —</option>
                <?php foreach ($listaAcad as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= academia_id() === (int) $a['id'] ? 'selected' : '' ?>>
                        <?= e($a['nombre']) ?><?= $a['estado'] === 'suspendida' ? ' (suspendida)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php else: ?>
        <div class="etiqueta-academia">
            <i class="bi bi-building"></i>
            <span><?= e(Auth::usuario('academia_nombre')) ?></span>
        </div>
    <?php endif; ?>

    <nav class="menu">
        <?php
        $grupoAnterior = null;
        foreach ($menu as $clave => $item):
            if (empty($item['menu']) || !Auth::esRol(...$item['roles'])) continue;
            $grupo = $item['grupo'] ?? 'academia';
            if ($grupo !== $grupoAnterior):
                $grupoAnterior = $grupo;
                ?>
                <div class="menu-grupo"><?= $grupo === 'sistema' ? 'Administración' : 'Academia' ?></div>
            <?php endif; ?>
            <a class="menu-item <?= activo($clave) ?>" href="<?= modulo($clave) ?>">
                <i class="bi <?= e($item['menu']['icono']) ?>"></i>
                <span><?= e($item['menu']['titulo']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="pie-lateral">
        <a class="menu-item <?= activo('clave') ?>" href="<?= modulo('clave') ?>">
            <i class="bi bi-shield-lock"></i><span>Mi contraseña</span>
        </a>
        <a class="menu-item" href="<?= modulo('salir') ?>">
            <i class="bi bi-box-arrow-left"></i><span>Salir</span>
        </a>
    </div>
</aside>

<main class="contenido">
    <header class="topbar">
        <button class="btn btn-sm btn-light" id="btnMenu" type="button" aria-label="Menú">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="titulo-pagina"><?= e($titulo ?? '') ?></h1>

        <div class="usuario-topbar">
            <button class="btn btn-sm btn-light" id="btnTema" type="button" aria-label="Cambiar tema">
                <i class="bi bi-moon-stars"></i>
            </button>
            <span class="avatar"><?= e(mb_strtoupper(mb_substr((string) Auth::usuario('nombre'), 0, 1))) ?></span>
            <div class="d-none d-sm-block">
                <div class="fw-semibold lh-1"><?= e(Auth::usuario('nombre')) ?></div>
                <small class="text-secondary"><?= e(es_super() ? 'Superadmin' : ucfirst(Auth::rol())) ?></small>
            </div>
        </div>
    </header>

    <div class="cuerpo">
        <?php foreach ((array) flash() as $f): ?>
            <div class="alert alert-<?= e($f['tipo']) ?> alert-dismissible fade show" role="alert">
                <?= e($f['texto']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
