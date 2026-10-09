<?php defined('APP_NOMBRE') or exit; ?>

<?php if (!$configurado): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        El lector de fichas todavía no está configurado.
        <?php if (es_super()): ?>
            Carga el token en <a href="<?= modulo('lector') ?>">Administración › Lector de fichas</a>.
        <?php else: ?>
            Pídele al administrador del sistema que cargue el token del lector.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-3">
    <!-- ================= Captura ================= -->
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-cabecera">
                <h2><i class="bi bi-camera me-2"></i>Capturar ficha</h2>
            </div>

            <div class="p-3">
                <div class="row g-2 mb-3">
                    <div class="col-sm-5">
                        <label class="form-label small mb-1" for="selPlantilla">Hoja</label>
                        <select class="form-select form-select-sm" id="selPlantilla">
                            <option value="<?= e($plantilla) ?>"><?= e($plantilla) ?></option>
                        </select>
                    </div>
                    <div class="col-sm-7">
                        <label class="form-label small mb-1" for="txtClave">
                            Clave <span class="text-secondary">(opcional, para la nota)</span>
                        </label>
                        <input type="text" class="form-control form-control-sm font-monospace" id="txtClave"
                               maxlength="300" autocomplete="off" spellcheck="false"
                               placeholder="ADEECBBAC…">
                    </div>
                </div>

                <!-- Cámara en vivo (necesita HTTPS o localhost) -->
                <div class="escaner-visor" id="visor" hidden>
                    <video id="video" playsinline muted></video>
                    <div class="escaner-guia"><span>Encuadra la ficha completa dentro del marco</span></div>
                </div>
                <img class="escaner-previa" id="previa" alt="Foto capturada" hidden>

                <div class="d-flex flex-wrap gap-2 mt-2">
                    <button type="button" class="btn btn-primary btn-sm" id="btnCamara">
                        <i class="bi bi-camera-video me-1"></i>Abrir cámara
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="btnCapturar" hidden>
                        <i class="bi bi-record-circle me-1"></i>Capturar y leer
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCerrar" hidden>
                        <i class="bi bi-x-lg me-1"></i>Cerrar cámara
                    </button>
                    <label class="btn btn-outline-primary btn-sm mb-0">
                        <i class="bi bi-image me-1"></i>Tomar o subir foto
                        <input type="file" id="inpFoto" accept="image/jpeg,image/png,image/webp" capture="environment" hidden>
                    </label>
                </div>
                <p class="small text-secondary mt-2 mb-0" id="ayudaCamara">
                    <i class="bi bi-lightbulb me-1"></i>
                    Ficha completa, de frente, con buena luz y sin sombras fuertes.
                </p>
            </div>

            <div class="panel-pie">
                <span class="small text-secondary" id="estadoEscaneo">Listo para escanear.</span>
                <div class="spinner-border spinner-border-sm text-primary" id="cargando" role="status" hidden>
                    <span class="visually-hidden">Leyendo…</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Resultado ================= -->
    <div class="col-lg-7">
        <div class="panel h-100">
            <div class="panel-cabecera d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-clipboard-check me-2"></i>Resultado</h2>
                <div class="d-flex gap-2" id="accionesResultado" hidden>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnComoClave"
                            title="La ficha del profesor: sus respuestas pasan a ser la clave">
                        <i class="bi bi-key me-1"></i>Usar como clave
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCopiar">
                        <i class="bi bi-clipboard me-1"></i>Copiar respuestas
                    </button>
                </div>
            </div>

            <div class="p-3" id="resultadoVacio">
                <div class="text-center text-secondary py-5">
                    <i class="bi bi-upc-scan display-5 d-block mb-2"></i>
                    Escanea una ficha para ver aquí sus respuestas.
                </div>
            </div>

            <div class="p-3" id="resultado" hidden>
                <div class="row g-2 mb-3" id="resumenEscaneo"></div>
                <div class="alert alert-warning py-2 small" id="avisoEscaneo" hidden></div>
                <div class="escaner-respuestas" id="gridRespuestas"></div>
                <div class="d-flex flex-wrap gap-3 small text-secondary mt-2">
                    <span><span class="escaner-muestra correcta"></span>correcta</span>
                    <span><span class="escaner-muestra incorrecta"></span>incorrecta</span>
                    <span><span class="escaner-muestra blanco"></span>en blanco</span>
                    <span><span class="escaner-muestra multiple"></span>doble marca</span>
                    <span><span class="escaner-muestra revisar"></span>revisar a mano</span>
                </div>
                <details class="mt-3">
                    <summary class="small">Ver la ficha corregida</summary>
                    <a id="lnkImagen" target="_blank" rel="noopener">
                        <img class="escaner-corregida mt-2" id="imgCorregida" alt="Ficha corregida">
                    </a>
                </details>
            </div>
        </div>
    </div>
</div>

<script>
    const OMR_CONFIGURADO = <?= json_encode($configurado) ?>;
</script>
<?php $scripts = ['escanear.js']; ?>
