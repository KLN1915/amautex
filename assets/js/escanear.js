/* Módulo Escanear fichas: cámara -> index.php?p=escanear&accion=calificar -> API OMR */
(() => {
    const $ = (id) => document.getElementById(id);
    const video = $('video'), visor = $('visor'), previa = $('previa');
    const btnCamara = $('btnCamara'), btnCapturar = $('btnCapturar'), btnCerrar = $('btnCerrar');
    const inpFoto = $('inpFoto'), txtClave = $('txtClave'), selPlantilla = $('selPlantilla');
    const estado = $('estadoEscaneo'), cargando = $('cargando');
    if (!video) return;

    const LADO_MAX = 2200;          // px: suficiente para leer, liviano para subir
    let flujo = null;               // MediaStream de la cámara
    let ultimo = null;              // último resultado (para "usar como clave")
    let ocupado = false;

    // ---------- preferencias del navegador (comodidad, no datos importantes)
    const guardado = (k, v) => { try { v === undefined ? (v = localStorage.getItem(k)) : localStorage.setItem(k, v); } catch { v = null; } return v; };
    txtClave.value = guardado('omr_clave') || '';
    txtClave.addEventListener('input', () => {
        txtClave.value = txtClave.value.toUpperCase().replace(/[^A-J\-]/g, '');
        guardado('omr_clave', txtClave.value);
    });

    // ---------- plantillas disponibles en el lector
    if (OMR_CONFIGURADO) {
        Api.get('escanear', 'plantillas').then((lista) => {
            if (!lista.length) return;
            const actual = guardado('omr_plantilla') || selPlantilla.value;
            selPlantilla.textContent = '';
            lista.forEach((p) => {
                const o = new Option(`${p.nombre} (${p.preguntas} preg.)`, p.nombre);
                o.selected = p.nombre === actual;
                selPlantilla.add(o);
            });
        }).catch(() => { /* se queda con la plantilla por defecto */ });
    }
    selPlantilla.addEventListener('change', () => guardado('omr_plantilla', selPlantilla.value));

    // ---------- cámara en vivo: solo existe en HTTPS o localhost
    const hayCamara = window.isSecureContext && navigator.mediaDevices?.getUserMedia;
    if (!hayCamara) {
        btnCamara.hidden = true;
        $('ayudaCamara').textContent =
            'Usa «Tomar o subir foto»: en el celular abre la cámara directamente. ' +
            '(La cámara en vivo dentro de la página requiere que el sistema esté en HTTPS.)';
    }

    async function abrirCamara() {
        try {
            flujo = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false,
            });
        } catch (e) {
            aviso(e.name === 'NotAllowedError'
                ? 'Permite el acceso a la cámara en el navegador para escanear.'
                : 'No se pudo abrir la cámara. Usa «Tomar o subir foto».', 'warning');
            return;
        }
        video.srcObject = flujo;
        await video.play();
        visor.hidden = false;
        previa.hidden = true;
        btnCamara.hidden = true;
        btnCapturar.hidden = btnCerrar.hidden = false;
        estado.textContent = 'Encuadra la ficha y pulsa «Capturar y leer».';
    }

    function cerrarCamara() {
        flujo?.getTracks().forEach((t) => t.stop());      // libera la cámara
        flujo = null;
        video.srcObject = null;
        visor.hidden = true;
        btnCapturar.hidden = btnCerrar.hidden = true;
        btnCamara.hidden = !hayCamara;
    }

    /** Dibuja una imagen/video en un canvas reducido y lo devuelve como JPEG. */
    function aJpeg(fuente, ancho, alto) {
        const f = Math.min(1, LADO_MAX / Math.max(ancho, alto));
        const c = document.createElement('canvas');
        c.width = Math.round(ancho * f);
        c.height = Math.round(alto * f);
        c.getContext('2d').drawImage(fuente, 0, 0, c.width, c.height);
        return new Promise((ok, mal) => c.toBlob((b) => (b ? ok(b) : mal(new Error('No se pudo procesar la foto.'))),
            'image/jpeg', 0.9));
    }

    async function capturar() {
        if (!video.videoWidth) return;
        const foto = await aJpeg(video, video.videoWidth, video.videoHeight);
        cerrarCamara();
        enviar(foto);
    }

    async function desdeArchivo() {
        const archivo = inpFoto.files[0];
        inpFoto.value = '';                 // permite elegir la misma foto otra vez
        if (!archivo) return;
        try {
            const bmp = await createImageBitmap(archivo);     // respeta la orientación del celular
            const foto = await aJpeg(bmp, bmp.width, bmp.height);
            bmp.close?.();
            enviar(foto);
        } catch {
            aviso('No se pudo abrir esa imagen. Usa una foto JPG o PNG.', 'danger');
        }
    }

    // ---------- envío al servidor (que la reenvía a la API con el token)
    async function enviar(foto) {
        if (ocupado) return;
        if (!OMR_CONFIGURADO) { aviso('Falta configurar el token del lector de fichas.', 'warning'); return; }
        ocupado = true;
        cargando.hidden = false;
        estado.textContent = 'Leyendo la ficha… (unos segundos)';
        mostrarPrevia(foto);

        const datos = new FormData();
        datos.append('imagen', foto, 'ficha.jpg');
        datos.append('clave', txtClave.value);
        datos.append('plantilla', selPlantilla.value);
        try {
            const r = await Api.post('escanear', 'calificar', datos);
            ultimo = r;
            pintar(r);
            estado.textContent = r.nota
                ? `Nota: ${r.nota.correctas} / ${r.nota.total}`
                : `${r.preguntas} respuestas leídas.`;
            aviso('Ficha leída.');
        } catch (e) {
            estado.textContent = 'No se pudo leer la ficha.';
            aviso(e.message, 'danger');
        } finally {
            ocupado = false;
            cargando.hidden = true;
        }
    }

    function mostrarPrevia(blob) {
        if (previa.src) URL.revokeObjectURL(previa.src);
        previa.src = URL.createObjectURL(blob);
        previa.hidden = false;
    }

    // ---------- resultado (todo con textContent: nada de la API se interpreta como HTML)
    function el(tag, clase, texto) {
        const n = document.createElement(tag);
        if (clase) n.className = clase;
        if (texto !== undefined) n.textContent = texto;
        return n;
    }

    function tarjeta(titulo, valor, color) {
        const col = el('div', 'col-6 col-md-3');
        const t = el('div', `escaner-dato border-${color}`);
        t.append(el('div', 'escaner-dato-valor', valor), el('div', 'escaner-dato-titulo', titulo));
        col.append(t);
        return col;
    }

    function pintar(r) {
        $('resultadoVacio').hidden = true;
        $('resultado').hidden = false;
        $('accionesResultado').hidden = false;

        const det = r.detalle || [];
        const blancos = det.filter((p) => p.estado === 'blanco').length;
        const multiples = det.filter((p) => p.estado === 'multiple').length;
        const resumen = $('resumenEscaneo');
        resumen.textContent = '';
        if (r.nota) {
            const pct = r.nota.total ? Math.round((100 * r.nota.correctas) / r.nota.total) : 0;
            resumen.append(
                tarjeta('Nota', `${r.nota.correctas}/${r.nota.total}`, 'primary'),
                tarjeta('Porcentaje', `${pct}%`, 'success'),
                tarjeta('Incorrectas', String(det.filter((p) => p.correcta === false && p.estado !== 'blanco').length), 'danger'),
                tarjeta('En blanco / doble', `${blancos} / ${multiples}`, 'secondary'),
            );
        } else {
            resumen.append(
                tarjeta('Preguntas', String(r.preguntas), 'primary'),
                tarjeta('Marcadas', String(r.preguntas - blancos), 'success'),
                tarjeta('En blanco / doble', `${blancos} / ${multiples}`, 'secondary'),
                tarjeta('Revisar', String(r.revisar.length), 'warning'),
            );
        }

        const avisoEl = $('avisoEscaneo');
        const textos = [];
        if (r.aviso) textos.push(r.aviso);
        if (r.revisar.length) textos.push(`Revisa a mano las preguntas ${r.revisar.join(', ')}: la marca no es del todo clara.`);
        avisoEl.textContent = textos.join(' ');
        avisoEl.hidden = !textos.length;

        const grid = $('gridRespuestas');
        grid.textContent = '';
        det.forEach((p) => {
            let clase = 'escaner-celda';
            if (p.estado === 'blanco') clase += ' blanco';
            else if (p.estado === 'multiple') clase += ' multiple';
            else if (p.correcta === true) clase += ' correcta';
            else if (p.correcta === false) clase += ' incorrecta';
            if (p.revisar) clase += ' revisar';
            const c = el('div', clase);
            c.append(el('span', 'escaner-num', String(p.n)), el('span', 'escaner-letra', p.respuesta || '–'));
            if (p.clave && p.correcta === false) c.append(el('span', 'escaner-clave', p.clave));
            c.title = [
                `Pregunta ${p.n}: ${p.respuesta || 'en blanco'}`,
                p.clave ? `clave ${p.clave}` : '',
                p.estado === 'multiple' ? 'doble marca' : p.estado === 'borron' ? 'tenía un borrón' : '',
                p.revisar ? 'revisar a mano' : '',
            ].filter(Boolean).join(' · ');
            grid.append(c);
        });

        // Foto corregida: data URI -> blob URL (los navegadores no abren data: en pestaña nueva)
        const img = $('imgCorregida'), lnk = $('lnkImagen');
        if (r.imagen_corregida) {
            fetch(r.imagen_corregida).then((x) => x.blob()).then((b) => {
                if (lnk.href.startsWith('blob:')) URL.revokeObjectURL(lnk.href);
                lnk.href = img.src = URL.createObjectURL(b);
            });
        }
    }

    // ---------- acciones del resultado
    $('btnComoClave').addEventListener('click', () => {
        if (!ultimo) return;
        const dudosas = (ultimo.detalle || []).filter((p) => !['ok', 'borron'].includes(p.estado) || p.revisar);
        const clave = (ultimo.detalle || []).map((p) => (['ok', 'borron'].includes(p.estado) ? p.respuesta : '-')).join('');
        if (dudosas.length && !confirmar(`Las preguntas ${dudosas.map((p) => p.n).join(', ')} no tienen una marca clara y quedarán anuladas ("-"). ¿Usar esta ficha como clave?`)) return;
        txtClave.value = clave;
        guardado('omr_clave', clave);
        aviso('Clave lista: las siguientes fichas se calificarán con ella.');
    });

    $('btnCopiar').addEventListener('click', async () => {
        if (!ultimo) return;
        const texto = ultimo.respuestas.map((x) => x || '-').join('');
        try { await navigator.clipboard.writeText(texto); aviso('Respuestas copiadas.'); }
        catch { window.prompt('Copia las respuestas:', texto); }
    });

    btnCamara.addEventListener('click', abrirCamara);
    btnCapturar.addEventListener('click', capturar);
    btnCerrar.addEventListener('click', cerrarCamara);
    inpFoto.addEventListener('change', desdeArchivo);
    window.addEventListener('pagehide', cerrarCamara);
})();
