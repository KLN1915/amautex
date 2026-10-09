<?php defined('APP_NOMBRE') or exit; ?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a class="btn btn-light btn-sm" href="<?= modulo('examenes') ?>"><i class="bi bi-arrow-left me-1"></i>Exámenes</a>
    <span class="fw-semibold"><?= e($examen['nombre']) ?></span>
    <span class="badge text-bg-light"><?= e($examen['grado_texto']) ?></span>
    <span class="badge text-bg-primary"><span id="contEscaneados"><?= (int) $escaneados ?></span> ficha(s) guardadas</span>
    <a class="btn btn-light btn-sm ms-auto" href="<?= modulo('examenes', ['accion' => 'resultados', 'id' => $examen['id']]) ?>">
        <i class="bi bi-trophy me-1"></i>Resultados
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <!-- Paso 1: alumno -->
        <div class="panel">
            <div class="panel-cabecera"><h2><span class="paso">1</span>Alumno</h2></div>
            <div class="p-3">
                <form class="input-group" id="formCodigo" autocomplete="off">
                    <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                    <input type="text" class="form-control text-uppercase font-monospace" id="txtCodigo"
                           maxlength="20" placeholder="Código del alumno (p. ej. A0001)" required autofocus>
                    <button class="btn btn-primary" type="submit">Buscar</button>
                </form>
                <div class="tarjeta-alumno mt-3" id="tarjetaAlumno" hidden>
                    <div class="fw-semibold" id="alNombre"></div>
                    <div class="small text-secondary" id="alDatos"></div>
                    <div class="alert alert-warning py-1 px-2 small mt-2 mb-0" id="alAviso" hidden></div>
                </div>
            </div>
        </div>

        <!-- Paso 2: ficha -->
        <div class="panel">
            <div class="panel-cabecera"><h2><span class="paso">2</span>Ficha del alumno</h2></div>
            <div class="p-3">
                <p class="small text-secondary" id="ayudaPaso2">Primero busca al alumno por su código.</p>
                <?= Vista::parcial('_camara') ?>
            </div>
        </div>
    </div>

    <!-- Paso 3: revisar y guardar -->
    <div class="col-lg-7">
        <div class="panel h-100">
            <div class="panel-cabecera"><h2><span class="paso">3</span>Revisar y guardar</h2></div>

            <div class="p-3" id="resultadoVacio">
                <div class="text-center text-secondary py-5">
                    <i class="bi bi-upc-scan display-5 d-block mb-2"></i>
                    Aquí aparecerá el resultado de la ficha escaneada.
                </div>
            </div>

            <div id="resultado" hidden>
                <div class="p-3">
                    <div class="row g-2 mb-3" id="resumen"></div>
                    <div class="alert alert-warning py-2 small" id="avisoRevisar" hidden></div>
                    <div class="escaner-respuestas" id="gridRespuestas"></div>
                    <div class="d-flex flex-wrap gap-3 small text-secondary mt-2">
                        <span><span class="escaner-muestra correcta"></span>buena</span>
                        <span><span class="escaner-muestra incorrecta"></span>mala</span>
                        <span><span class="escaner-muestra blanco"></span>en blanco</span>
                        <span><span class="escaner-muestra multiple"></span>doble marca</span>
                        <span><span class="escaner-muestra revisar"></span>revisar a mano</span>
                    </div>
                    <details class="mt-3">
                        <summary class="small">Ver la ficha corregida</summary>
                        <img class="escaner-corregida mt-2" id="imgCorregida" alt="Ficha corregida">
                    </details>
                </div>

                <form class="panel-pie flex-wrap gap-2" id="formGuardar" autocomplete="off">
                    <div class="d-flex flex-wrap align-items-end gap-2">
                        <div>
                            <label class="form-label small mb-0" for="entFecha">Entregó el</label>
                            <input type="date" class="form-control form-control-sm" id="entFecha" required>
                        </div>
                        <div>
                            <label class="form-label small mb-0" for="entHora">a las</label>
                            <input type="time" class="form-control form-control-sm" id="entHora">
                        </div>
                    </div>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDescartar">
                            <i class="bi bi-x-lg me-1"></i>Descartar
                        </button>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-save me-1"></i>Guardar resultado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const EXAMEN = <?= json_encode(['id' => (int) $examen['id'], 'preguntas' => (int) $examen['preguntas']]) ?>;
</script>
<?php $scripts = ['camara.js', 'examen_escanear.js']; ?>
