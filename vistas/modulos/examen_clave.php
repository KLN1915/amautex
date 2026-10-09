<?php defined('APP_NOMBRE') or exit; ?>
<?php $escaneados = examenes_contar_resultados((int) $examen['id']); ?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a class="btn btn-light btn-sm" href="<?= modulo('examenes') ?>"><i class="bi bi-arrow-left me-1"></i>Exámenes</a>
    <span class="fw-semibold"><?= e($examen['nombre']) ?></span>
    <span class="badge text-bg-light"><?= e($examen['grado_texto']) ?></span>
    <span class="badge text-bg-light"><?= (int) $examen['preguntas'] ?> preguntas · hoja <?= e($examen['plantilla']) ?></span>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-cabecera"><h2><i class="bi bi-magic me-2"></i>Proponer desde una hoja</h2></div>
            <div class="p-3">
                <p class="small text-secondary">
                    Escanea una hoja <strong>marcada con las respuestas correctas</strong> y el sistema las propone.
                    También sirve una hoja <strong>en blanco</strong>: listará las preguntas para que las marques aquí.
                </p>
                <?= Vista::parcial('_camara') ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <form class="panel" id="formClave">
            <div class="panel-cabecera">
                <h2><i class="bi bi-key me-2"></i>Respuestas correctas</h2>
                <span class="small" id="progresoClave"></span>
            </div>
            <div class="p-3">
                <div class="small text-secondary mb-2">
                    Elige la alternativa correcta de cada pregunta. <strong>–</strong> anula la pregunta (no cuenta para nadie).
                    <span class="clave-muestra pendiente"></span>falta elegir o la marca no era clara.
                </div>
                <div class="clave-grid" id="gridClave"></div>
            </div>
            <div class="panel-pie flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimpiar">
                    <i class="bi bi-eraser me-1"></i>Limpiar
                </button>
                <div class="ms-auto d-flex gap-2">
                    <a class="btn btn-outline-primary btn-sm <?= $examen['clave'] ? '' : 'd-none' ?>" id="btnIrEscanear"
                       href="<?= modulo('examenes', ['accion' => 'escanear', 'id' => $examen['id']]) ?>">
                        <i class="bi bi-camera me-1"></i>Escanear alumnos
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Guardar respuestas</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const EXAMEN = <?= json_encode(['id' => (int) $examen['id'], 'preguntas' => (int) $examen['preguntas'],
                                   'clave' => $examen['clave'], 'escaneados' => $escaneados]) ?>;
    const LETRAS = <?= json_encode(str_split($letras)) ?>;
</script>
<?php $scripts = ['camara.js', 'examen_clave.js']; ?>
