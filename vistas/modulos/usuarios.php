<?php defined('APP_NOMBRE') or exit; ?>
<?php $global = academia_id() === null; ?>

<?php if ($global): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        Estás viendo los usuarios de <strong>todas las academias</strong>.
        Para crear o editar usuarios, selecciona primero una academia en el menú lateral.
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-cabecera">
        <h2>
            Usuarios<?= $global ? '' : ' de ' . e(academia_nombre()) ?>
            <?php if ($cupo): ?>
                <small class="text-secondary ms-2">
                    <?= (int) $cupo['usados'] ?> / <?= (int) $cupo['limite'] ?> del plan
                </small>
            <?php endif; ?>
        </h2>
        <div class="d-flex gap-2">
            <input type="search" id="buscarUsuario" class="form-control form-control-sm" placeholder="Buscar…" style="width:220px">
            <?php if (!$global): ?>
                <button class="btn btn-primary btn-sm" id="btnNuevoUsuario">
                    <i class="bi bi-plus-lg me-1"></i>Nuevo
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th><th>Usuario</th>
                    <?php if ($global): ?><th>Academia</th><?php endif; ?>
                    <th>Correo</th><th>Rol</th><th>Estado</th><th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaUsuarios" data-global="<?= $global ? 1 : 0 ?>">
                <tr><td colspan="7" class="text-center py-4 text-secondary">Cargando…</td></tr>
            </tbody>
        </table>
    </div>

    <div class="panel-pie">
        <span class="text-secondary small" id="totalUsuarios"></span>
        <nav><ul class="pagination pagination-sm mb-0" id="paginacionUsuarios"></ul></nav>
    </div>
</div>

<!-- Modal de alta / edición -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" id="formUsuario">
        <div class="modal-header">
            <h5 class="modal-title">Nuevo usuario</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id" value="0">
            <p class="text-secondary small">
                El usuario se crea dentro de <strong><?= e(academia_nombre()) ?></strong>.
            </p>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" name="nombre" class="form-control" required maxlength="100">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Usuario *</label>
                    <input type="text" name="usuario" class="form-control" required maxlength="50">
                    <div class="form-text">Debe ser único en todo el sistema.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo</label>
                    <input type="email" name="correo" class="form-control" maxlength="120">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Rol *</label>
                    <select name="rol" class="form-select" required>
                        <?php foreach ($roles as $valor => $texto): ?>
                            <option value="<?= e($valor) ?>"><?= e($texto) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="clave" class="form-control" minlength="6"
                           placeholder="Dejar vacío para no cambiar">
                </div>
                <div class="col-12 form-check ms-2">
                    <input class="form-check-input" type="checkbox" name="activo" value="1" id="chkActivo" checked>
                    <label class="form-check-label" for="chkActivo">Usuario activo</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
    </form>
  </div>
</div>

<?php $scripts = ['usuarios.js']; ?>
