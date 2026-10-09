/* Captura de fichas reutilizable (vista vistas/modulos/_camara.php).
 *
 *   const cam = Camara.iniciar({ alFoto: async (blob) => { ... } });
 *   cam.habilitar(false)   // bloquea los botones (p. ej. mientras no hay alumno)
 *
 * Cámara en vivo cuando el sitio está en HTTPS; si no, «Tomar o subir foto» (en el
 * celular abre la cámara directamente). La foto se reduce antes de subirla. */
const Camara = (() => {
    const LADO_MAX = 2200;

    function aJpeg(fuente, ancho, alto) {
        const f = Math.min(1, LADO_MAX / Math.max(ancho, alto));
        const c = document.createElement('canvas');
        c.width = Math.round(ancho * f);
        c.height = Math.round(alto * f);
        c.getContext('2d').drawImage(fuente, 0, 0, c.width, c.height);
        return new Promise((ok, mal) => c.toBlob((b) => (b ? ok(b) : mal(new Error('No se pudo procesar la foto.'))),
            'image/jpeg', 0.9));
    }

    function iniciar({ alFoto }) {
        const $ = (id) => document.getElementById(id);
        const video = $('video'), visor = $('visor'), previa = $('previa');
        const btnCamara = $('btnCamara'), btnCapturar = $('btnCapturar'), btnCerrar = $('btnCerrar');
        const inpFoto = $('inpFoto'), lblFoto = $('lblFoto'), cargando = $('cargando');
        let flujo = null, ocupado = false;

        const hayCamara = window.isSecureContext && navigator.mediaDevices?.getUserMedia;
        if (!hayCamara) {
            btnCamara.hidden = true;
            $('ayudaCamara').textContent = 'Usa «Tomar o subir foto»: en el celular abre la cámara directamente. '
                + '(La cámara dentro de la página requiere que el sistema esté en HTTPS.)';
        }

        async function abrir() {
            try {
                flujo = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
                    audio: false,
                });
            } catch (e) {
                aviso(e.name === 'NotAllowedError'
                    ? 'Permite el acceso a la cámara en el navegador.'
                    : 'No se pudo abrir la cámara. Usa «Tomar o subir foto».', 'warning');
                return;
            }
            video.srcObject = flujo;
            await video.play();
            visor.hidden = false;
            previa.hidden = true;
            btnCamara.hidden = true;
            btnCapturar.hidden = btnCerrar.hidden = false;
        }

        function cerrar() {
            flujo?.getTracks().forEach((t) => t.stop());
            flujo = null;
            video.srcObject = null;
            visor.hidden = true;
            btnCapturar.hidden = btnCerrar.hidden = true;
            btnCamara.hidden = !hayCamara;
        }

        async function entregar(blob) {
            if (ocupado) return;
            ocupado = true;
            cargando.hidden = false;
            if (previa.src) URL.revokeObjectURL(previa.src);
            previa.src = URL.createObjectURL(blob);
            previa.hidden = false;
            try { await alFoto(blob); } finally { ocupado = false; cargando.hidden = true; }
        }

        btnCamara.addEventListener('click', abrir);
        btnCerrar.addEventListener('click', cerrar);
        btnCapturar.addEventListener('click', async () => {
            if (!video.videoWidth) return;
            const blob = await aJpeg(video, video.videoWidth, video.videoHeight);
            cerrar();
            entregar(blob);
        });
        inpFoto.addEventListener('change', async () => {
            const archivo = inpFoto.files[0];
            inpFoto.value = '';
            if (!archivo) return;
            try {
                const bmp = await createImageBitmap(archivo);
                const blob = await aJpeg(bmp, bmp.width, bmp.height);
                bmp.close?.();
                entregar(blob);
            } catch {
                aviso('No se pudo abrir esa imagen. Usa una foto JPG o PNG.', 'danger');
            }
        });
        window.addEventListener('pagehide', cerrar);

        return {
            habilitar(si) {
                btnCamara.disabled = btnCapturar.disabled = !si;
                lblFoto.classList.toggle('disabled', !si);
                inpFoto.disabled = !si;
                if (!si) cerrar();
            },
            limpiar() {
                previa.hidden = true;
                if (previa.src) URL.revokeObjectURL(previa.src);
                previa.removeAttribute('src');
            },
            cerrar,
        };
    }

    return { iniciar };
})();

/** Utilidad compartida: crea un elemento con clase y texto (sin interpretar HTML). */
function nodo(tag, clase, texto) {
    const n = document.createElement(tag);
    if (clase) n.className = clase;
    if (texto !== undefined && texto !== null) n.textContent = texto;
    return n;
}
