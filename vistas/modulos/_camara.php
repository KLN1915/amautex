<?php defined('APP_NOMBRE') or exit; ?>
<?php /* Bloque de captura reutilizable (lo maneja assets/js/camara.js). */ ?>
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
    <label class="btn btn-outline-primary btn-sm mb-0" id="lblFoto">
        <i class="bi bi-image me-1"></i>Tomar o subir foto
        <input type="file" id="inpFoto" accept="image/jpeg,image/png,image/webp" capture="environment" hidden>
    </label>
    <span class="spinner-border spinner-border-sm text-primary align-self-center" id="cargando" role="status" hidden></span>
</div>
<p class="small text-secondary mt-2 mb-0" id="ayudaCamara">
    <i class="bi bi-lightbulb me-1"></i>Ficha completa, de frente, con buena luz y sin sombras fuertes.
</p>
