<?php defined('APP_NOMBRE') or exit; ?>

<div id="avisoCupo">
<?php if ($cupo['limite'] > 0 && $cupo['usados'] >= $cupo['limite']): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        La academia alcanzó el límite de <strong><?= (int) $cupo['limite'] ?> alumnos</strong> de su plan.
        Para registrar más, hay que ampliar el plan.
    </div>
<?php endif; ?>
</div>

<div class="panel">
    <div class="panel-cabecera">
        <h2>
            Alumnos
            <small class="text-secondary ms-2" id="cupoAlumnos"><?= (int) $cupo['usados'] ?> / <?= (int) $cupo['limite'] ?></small>
        </h2>
        <div class="d-flex gap-2 flex-wrap">
            <select id="filtroTipo" class="form-select form-select-sm" style="width:150px">
                <option value="">Todo tipo</option>
                <?php foreach ($tipos as $clave => $t): ?>
                    <option value="<?= e($clave) ?>"><?= e($t['titulo']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filtroColegio" class="form-select form-select-sm" style="width:190px">
                <option value="">Todo colegio</option>
                <?php foreach ($colegios as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filtroNivel" class="form-select form-select-sm" style="width:140px">
                <option value="">Todo nivel</option>
                <?php foreach ($niveles as $clave => $n): ?>
                    <option value="<?= e($clave) ?>"><?= e($n['titulo']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filtroGrado" class="form-select form-select-sm" style="width:120px">
                <option value="">Todo grado</option>
            </select>
            <input type="search" id="buscarAlumno" class="form-control form-control-sm"
                   placeholder="Código, nombre o colegio…" style="width:200px">
            <?php if (puede_editar()): ?>
                <button class="btn btn-primary btn-sm" id="btnNuevoAlumno">
                    <i class="bi bi-plus-lg me-1"></i>Nuevo alumno
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Apellidos y nombres</th>
                    <th>Colegio</th>
                    <th>Nivel y grado</th>
                    <th class="text-end">Cuota</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaAlumnos">
                <tr><td colspan="7" class="text-center py-4 text-secondary">Cargando…</td></tr>
            </tbody>
        </table>
    </div>

    <div class="panel-pie">
        <span class="text-secondary small" id="totalAlumnos"></span>
        <nav><ul class="pagination pagination-sm mb-0" id="paginacionAlumnos"></ul></nav>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel h-100">
            <div class="panel-cabecera">
                <h2><i class="bi bi-bar-chart me-2"></i>Alumnos activos por nivel y grado</h2>
            </div>
            <div class="p-3 d-flex flex-wrap gap-2" id="panelResumen">
                <?php if (!$resumen): ?>
                    <span class="text-secondary small">Todavía no hay alumnos registrados.</span>
                <?php endif; ?>
                <?php foreach ($resumen as $r): ?>
                    <span class="pastilla-resumen">
                        <?= e(alumnos_grado_texto($r['nivel'], $r['grado'])) ?>
                        <strong><?= (int) $r['total'] ?></strong>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="panel h-100">
            <div class="panel-cabecera">
                <h2><i class="bi bi-cash-coin me-2"></i>Montos de inscripción</h2>
                <a class="btn btn-sm btn-light" href="<?= modulo('precios') ?>">
                    <i class="bi bi-sliders me-1"></i>Configurar
                </a>
            </div>
            <div class="p-3">
                <?php $porTipo = array_column($resumenTipo, 'total', 'tipo_alumno'); ?>
                <dl class="lista-datos mb-0" id="panelCuotas">
                    <?php foreach ($tipos as $clave => $t): ?>
                        <dt>
                            <?= e($t['titulo']) ?>
                            <span class="text-secondary">· <?= (int) ($porTipo[$clave] ?? 0) ?> alumno(s)</span>
                        </dt>
                        <dd class="fw-semibold"><?= moneda($tarifas[$clave]) ?></dd>
                    <?php endforeach; ?>
                </dl>
                <p class="text-secondary small mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    La cuota sale del tipo de colegio del alumno. Los montos se
                    configuran en el módulo <strong>Precios</strong>.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ---------- Modal: alta / edición ---------- -->
<div class="modal fade" id="modalAlumno" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" id="formAlumno">
        <div class="modal-header">
            <h5 class="modal-title">Nuevo alumno</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id" value="0">

            <h6 class="titulo-seccion">Datos del alumno</h6>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Código</label>
                    <input type="text" name="codigo" class="form-control" maxlength="20">
                    <div class="form-text">Vacío = correlativo automático.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Apellido paterno *</label>
                    <input type="text" name="apellido_paterno" class="form-control" required maxlength="60">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Apellido materno</label>
                    <input type="text" name="apellido_materno" class="form-control" maxlength="60">
                </div>
                <div class="col-12">
                    <label class="form-label">Nombres *</label>
                    <input type="text" name="nombres" class="form-control" required maxlength="100">
                </div>
            </div>

            <h6 class="titulo-seccion mt-4">Procedencia y cuota</h6>
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label d-block">Tipo de alumno *</label>
                    <div class="selector-tipo" id="selectorTipo">
                        <?php foreach ($tipos as $clave => $t): ?>
                            <input type="radio" class="btn-check" name="tipo_alumno" id="tipo_<?= e($clave) ?>"
                                   value="<?= e($clave) ?>" <?= $clave === 'estatal' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="tipo_<?= e($clave) ?>">
                                <?= e($t['titulo']) ?>
                                <span class="badge text-bg-light ms-1"><?= moneda($tarifas[$clave]) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-md-8" id="bloqueColegio">
                    <label class="form-label">Colegio *</label>

                    <?php /* Tom Select lo convierte en un combobox con buscador. */ ?>
                    <select name="colegio_id" id="selColegio" class="form-select"
                            placeholder="Buscar o elegir colegio…"></select>
                    <input type="text" name="colegio_nuevo" id="colegioNuevo"
                           class="form-control mt-2 d-none" maxlength="150"
                           placeholder="Nombre del colegio nuevo">

                    <div class="form-text" id="ayudaColegio">
                        <i class="bi bi-plus-circle me-1"></i>
                        Elige de la lista, o «Agregar colegio nuevo» para registrarlo al vuelo.
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Nivel *</label>
                    <select name="nivel" id="selNivel" class="form-select" required>
                        <?php foreach ($niveles as $clave => $n): ?>
                            <option value="<?= e($clave) ?>" <?= $clave === 'secundaria' ? 'selected' : '' ?>>
                                <?= e($n['titulo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Grado *</label>
                    <select name="grado" id="selGrado" class="form-select"></select>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="activo" value="1" id="chkAlumnoActivo" checked>
                        <label class="form-check-label" for="chkAlumnoActivo">Alumno activo</label>
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

<script>
    /* Grados válidos por nivel y datos de los tipos: los define el modelo en
       PHP y el formulario los replica, para no duplicar la regla en dos idiomas. */
    const NIVELES = <?= json_encode(array_map(fn($n) => $n['grados'], $niveles), JSON_UNESCAPED_UNICODE) ?>;
    const TIPOS   = <?= json_encode($tipos, JSON_UNESCAPED_UNICODE) ?>;
    const TARIFAS = <?= json_encode($tarifas) ?>;

    /* El rol `consulta` solo mira: la tabla se pinta sin acciones. Esconderlas
       es comodidad, no seguridad; quien manda es Auth::exigirRol() en el
       controlador, que rechaza guardar, estado y eliminar. */
    const PUEDE_EDITAR = <?= json_encode(puede_editar()) ?>;
</script>
<?php
$libs    = ['https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js'];
$scripts = ['alumnos.js'];
?>
