<?php defined('APP_NOMBRE') or exit; ?>
<div class="panel">
    <div class="panel-cabecera">
        <h2>Academias que usan el sistema</h2>
        <div class="d-flex gap-2 flex-wrap">
            <select id="filtroEstado" class="form-select form-select-sm" style="width:160px">
                <option value="">Todos los estados</option>
                <option value="activa">Activas</option>
                <option value="suspendida">Suspendidas</option>
                <option value="vencida">Con plan vencido</option>
            </select>
            <input type="search" id="buscarAcademia" class="form-control form-control-sm"
                   placeholder="Buscar…" style="width:200px">
            <button class="btn btn-primary btn-sm" id="btnNuevaAcademia">
                <i class="bi bi-plus-lg me-1"></i>Nueva academia
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Academia</th><th>Plan</th><th>Vencimiento</th>
                    <th class="text-center">Usuarios</th><th class="text-center">Alumnos</th>
                    <th>Estado</th><th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaAcademias">
                <tr><td colspan="7" class="text-center py-4 text-secondary">Cargando…</td></tr>
            </tbody>
        </table>
    </div>

    <div class="panel-pie">
        <span class="text-secondary small" id="totalAcademias"></span>
        <nav><ul class="pagination pagination-sm mb-0" id="paginacionAcademias"></ul></nav>
    </div>
</div>

<!-- ---------- Modal: alta / edición de academia ---------- -->
<div class="modal fade" id="modalAcademia" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" id="formAcademia">
        <div class="modal-header">
            <h5 class="modal-title">Nueva academia</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id" value="0">

            <h6 class="titulo-seccion">Datos de la academia</h6>
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control" required maxlength="120">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Código *</label>
                    <input type="text" name="codigo" class="form-control" required maxlength="30"
                           placeholder="ej. sanmarcos">
                    <div class="form-text">Identificador corto, sin espacios.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">RUC</label>
                    <input type="text" name="ruc" class="form-control" maxlength="20">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" maxlength="30">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Correo</label>
                    <input type="email" name="correo" class="form-control" maxlength="120">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control" maxlength="180">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Color de marca</label>
                    <input type="color" name="color" class="form-control form-control-color w-100" value="#2563eb">
                </div>
            </div>

            <h6 class="titulo-seccion mt-4">Plan de alquiler</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Plan</label>
                    <select name="plan" class="form-select">
                        <option value="prueba">Prueba</option>
                        <option value="basico">Básico</option>
                        <option value="pro">Pro</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Inicio</label>
                    <input type="date" name="inicio_plan" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vence</label>
                    <input type="date" name="fin_plan" class="form-control">
                    <div class="form-text">Vacío = sin vencimiento.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Límite de usuarios</label>
                    <input type="number" name="limite_usuarios" class="form-control" min="1" value="10">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Límite de alumnos</label>
                    <input type="number" name="limite_alumnos" class="form-control" min="1" value="200">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="activa">Activa</option>
                        <option value="suspendida">Suspendida</option>
                    </select>
                </div>
            </div>

            <p class="text-secondary small mt-3 mb-0">
                <i class="bi bi-cash-coin me-1"></i>
                Las cuotas que cobra la academia (estatal / particular / libre) se configuran
                dentro de la academia, en el módulo <strong>Precios</strong>.
            </p>

            <div id="bloqueAdmin">
                <h6 class="titulo-seccion mt-4">Administrador de la academia</h6>
                <p class="text-secondary small mb-3">
                    Se crea junto con la academia. Estas son las credenciales que entregas al cliente.
                </p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="admin_nombre" class="form-control" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Usuario *</label>
                        <input type="text" name="admin_usuario" class="form-control" maxlength="50">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Correo</label>
                        <input type="email" name="admin_correo" class="form-control" maxlength="120">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contraseña</label>
                        <input type="text" name="admin_clave" class="form-control" minlength="6"
                               placeholder="Vacío = se genera automáticamente">
                    </div>
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

<!-- ---------- Modal: credenciales de la academia ---------- -->
<div class="modal fade" id="modalCredenciales" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Credenciales</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div id="credencialesNueva" class="alert alert-success d-none">
                <div class="fw-semibold mb-1"><i class="bi bi-key me-1"></i>Nueva contraseña generada</div>
                <div class="caja-credencial">
                    <code id="credencialTexto"></code>
                    <button class="btn btn-sm btn-outline-success" id="btnCopiarClave" type="button">
                        <i class="bi bi-clipboard"></i> Copiar
                    </button>
                </div>
                <small class="d-block mt-1">Cópiala ahora: no se vuelve a mostrar.</small>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Último acceso</th><th class="text-end">Clave</th></tr>
                    </thead>
                    <tbody id="tablaCredenciales"></tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
        </div>
    </div>
  </div>
</div>

<?php $scripts = ['academias.js']; ?>
