<?php defined('APP_NOMBRE') or exit; ?>

<?php if (!$lectorListo): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        El lector de fichas no está configurado: no se podrán escanear hojas.
        <?= es_super() ? '<a href="' . modulo('lector') . '">Configúralo aquí</a>.' : 'Avisa al administrador del sistema.' ?>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-cabecera">
        <h2><i class="bi bi-ui-checks-grid me-2"></i>Exámenes</h2>
        <?php if ($puedeEditar): ?>
            <button class="btn btn-primary btn-sm" id="btnNuevoExamen">
                <i class="bi bi-plus-lg me-1"></i>Nuevo examen
            </button>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Examen</th>
                    <th>Nivel / grado</th>
                    <th class="text-center">Preguntas</th>
                    <th class="text-center">Respuestas</th>
                    <th class="text-center">Fichas</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaExamenes">
                <tr><td colspan="6" class="text-center py-4 text-secondary">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
    <div class="panel-pie small text-secondary">
        <span><i class="bi bi-info-circle me-1"></i>
            Pasos: <strong>1</strong> crear el examen · <strong>2</strong> configurar las respuestas ·
            <strong>3</strong> escanear las fichas de los alumnos.</span>
    </div>
</div>

<form class="panel" id="formReglas" style="max-width: 760px">
    <div class="panel-cabecera">
        <h2><i class="bi bi-calculator me-2"></i>Puntaje de la academia</h2>
        <span class="small text-secondary">Vale para todos los exámenes</span>
    </div>
    <div class="p-3">
        <div class="row g-3 align-items-end">
            <?php foreach (['correcta' => ['Correcta', 'text-success', 'bi-check-circle'],
                            'incorrecta' => ['Incorrecta o doble marca', 'text-danger', 'bi-x-circle'],
                            'blanco' => ['En blanco', 'text-secondary', 'bi-circle']] as $k => [$t, $color, $icono]): ?>
                <div class="col-sm-4">
                    <label class="form-label small" for="regla_<?= $k ?>">
                        <i class="bi <?= $icono ?> <?= $color ?> me-1"></i><?= e($t) ?>
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.25" min="-1000" max="1000" class="form-control text-end"
                               id="regla_<?= $k ?>" name="<?= $k ?>" value="<?= e(rtrim(rtrim(number_format($reglas[$k], 2, '.', ''), '0'), '.')) ?>"
                               <?= $puedeBorrar ? '' : 'disabled' ?> required>
                        <span class="input-group-text">pts</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="small text-secondary mt-2 mb-0" id="ejemploReglas"></p>
    </div>
    <?php if ($puedeBorrar): ?>
        <div class="panel-pie">
            <span class="small text-secondary"><i class="bi bi-arrow-repeat me-1"></i>Al cambiarlo se actualizan los puntajes de todos los exámenes.</span>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Guardar puntaje</button>
        </div>
    <?php endif; ?>
</form>

<?php if ($puedeEditar): ?>
<div class="modal fade" id="modalExamen" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" id="formExamen" autocomplete="off">
        <div class="modal-header">
            <h5 class="modal-title">Nuevo examen</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id" value="0">
            <div class="mb-3">
                <label class="form-label" for="exNombre">Nombre</label>
                <input type="text" class="form-control" id="exNombre" name="nombre" maxlength="120" required
                       placeholder="p. ej. Simulacro de matemática – octubre">
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label" for="exNivel">Nivel</label>
                    <select class="form-select" id="exNivel" name="nivel" required>
                        <?php foreach ($niveles as $clave => $n): ?>
                            <option value="<?= e($clave) ?>" <?= $clave === 'secundaria' ? 'selected' : '' ?>><?= e($n['titulo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label" for="exGrado">Grado</label>
                    <select class="form-select" id="exGrado" name="grado"></select>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label" for="exFecha">Fecha <span class="text-secondary small">(opcional)</span></label>
                    <input type="date" class="form-control" id="exFecha" name="fecha">
                </div>
                <div class="col-6">
                    <label class="form-label" for="exPlantilla">Hoja de respuestas</label>
                    <select class="form-select" id="exPlantilla" name="plantilla">
                        <option value="<?= e($plantilla) ?>"><?= e($plantilla) ?></option>
                    </select>
                </div>
            </div>
            <div class="form-text" id="ayudaHoja">La hoja define cuántas preguntas y alternativas tiene el examen.</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
        </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
    const NIVELES = <?= json_encode(array_map(fn($n) => $n['grados'], $niveles), JSON_UNESCAPED_UNICODE) ?>;
    const PUEDE_EDITAR = <?= json_encode($puedeEditar) ?>;
    const PUEDE_BORRAR = <?= json_encode($puedeBorrar) ?>;
</script>
<?php $scripts = ['examenes.js']; ?>
