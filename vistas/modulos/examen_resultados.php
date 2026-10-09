<?php defined('APP_NOMBRE') or exit; ?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a class="btn btn-light btn-sm" href="<?= modulo('examenes') ?>"><i class="bi bi-arrow-left me-1"></i>Exámenes</a>
    <span class="fw-semibold"><?= e($examen['nombre']) ?></span>
    <span class="badge text-bg-light"><?= e($examen['grado_texto']) ?></span>
    <div class="ms-auto d-flex gap-2">
        <?php if ($puedeEditar && $examen['clave']): ?>
            <a class="btn btn-primary btn-sm" href="<?= modulo('examenes', ['accion' => 'escanear', 'id' => $examen['id']]) ?>">
                <i class="bi bi-camera me-1"></i>Escanear más
            </a>
        <?php endif; ?>
        <a class="btn btn-outline-success btn-sm" href="<?= modulo('examenes', ['accion' => 'exportar', 'id' => $examen['id']]) ?>">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar a Excel
        </a>
    </div>
</div>

<div class="row g-2 mb-3" id="resumenResultados"></div>

<div class="panel">
    <div class="panel-cabecera">
        <h2><i class="bi bi-trophy me-2"></i>Ranking</h2>
        <span class="small text-secondary" id="reglasTexto"></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th>Alumno</th>
                    <th class="text-center text-success">Buenas</th>
                    <th class="text-center text-danger">Malas</th>
                    <th class="text-center">Blanco</th>
                    <th class="text-end">Puntaje</th>
                    <th>Entregó</th>
                    <th class="text-end">Ficha</th>
                </tr>
            </thead>
            <tbody id="tablaResultados">
                <tr><td colspan="8" class="text-center py-4 text-secondary">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
    <div class="panel-pie small text-secondary">
        <span><i class="bi bi-sort-down me-1"></i>A igual puntaje va primero quien entregó antes.</span>
    </div>
</div>

<div class="modal fade" id="modalFicha" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="fichaTitulo">Ficha</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-lg-6"><img class="escaner-corregida" id="fichaImagen" alt="Ficha corregida"></div>
                <div class="col-lg-6">
                    <div class="row g-2 mb-3" id="fichaResumen"></div>
                    <div class="escaner-respuestas" id="fichaGrid"></div>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>

<script>
    const EXAMEN_ID = <?= (int) $examen['id'] ?>;
    const PUEDE_EDITAR = <?= json_encode($puedeEditar) ?>;
</script>
<?php $scripts = ['camara.js', 'examen_resultados.js']; ?>
