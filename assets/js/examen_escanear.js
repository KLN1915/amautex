/* Escanear las fichas de los alumnos: código -> foto -> revisar -> guardar. */
(() => {
    const $ = (id) => document.getElementById(id);
    const txtCodigo = $('txtCodigo');
    if (!txtCodigo) return;

    let alumno = null;     // alumno elegido
    let escaneo = null;    // id del escaneo que el servidor guardó en la sesión

    const cam = Camara.iniciar({ alFoto: leerFicha });
    cam.habilitar(false);

    // ---------------------------------------------------------------- paso 1: alumno
    $('formCodigo').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const codigo = txtCodigo.value.trim().toUpperCase();
        if (!codigo) return;
        if (escaneo && !confirmar('Hay una ficha leída sin guardar. ¿Descartarla y cambiar de alumno?')) return;
        limpiarResultado();
        try {
            alumno = await Api.get('examenes', 'alumno', { examen: EXAMEN.id, codigo });
        } catch (e) {
            alumno = null;
            $('tarjetaAlumno').hidden = true;
            cam.habilitar(false);
            aviso(e.message, 'warning');
            txtCodigo.select();
            return;
        }
        $('alNombre').textContent = alumno.nombre_completo;
        $('alDatos').textContent = [alumno.codigo, alumno.grado_texto, alumno.colegio].filter(Boolean).join(' · ');
        const avisos = [];
        if (!Number(alumno.activo)) avisos.push('El alumno está inactivo.');
        if (alumno.otro_grado) avisos.push('Este examen es de otro nivel o grado que el del alumno.');
        if (alumno.previo) avisos.push(`Ya tiene una ficha guardada (${alumno.previo.correctas}/${alumno.previo.total} buenas): si guardas, se reemplaza.`);
        $('alAviso').textContent = avisos.join(' ');
        $('alAviso').hidden = !avisos.length;
        $('tarjetaAlumno').hidden = false;
        $('ayudaPaso2').textContent = 'Escanea la ficha de ' + alumno.nombres + '.';
        cam.habilitar(true);
    });

    // ---------------------------------------------------------------- paso 2: ficha
    async function leerFicha(blob) {
        if (!alumno) return;
        const datos = new FormData();
        datos.append('examen', EXAMEN.id);
        datos.append('alumno', alumno.id);
        datos.append('imagen', blob, `${alumno.codigo}.jpg`);
        try {
            const r = await Api.post('examenes', 'leer_alumno', datos);
            escaneo = r.escaneo;
            pintar(r);
        } catch (e) {
            aviso(e.message, 'danger');
        }
    }

    // ---------------------------------------------------------------- paso 3: revisar
    function tarjeta(titulo, valor, color) {
        const col = nodo('div', 'col-6 col-md-3');
        const t = nodo('div', `escaner-dato border-${color}`);
        t.append(nodo('div', 'escaner-dato-valor', valor), nodo('div', 'escaner-dato-titulo', titulo));
        col.append(t);
        return col;
    }
    const num = (v) => String(Number(v)).replace(/\.0+$/, '');

    function pintar(r) {
        const c = r.conteo;
        $('resultadoVacio').hidden = true;
        $('resultado').hidden = false;

        $('resumen').replaceChildren(
            tarjeta('Buenas', String(c.correctas), 'success'),
            tarjeta('Malas', String(c.incorrectas), 'danger'),
            tarjeta('En blanco', String(c.blancos), 'secondary'),
            tarjeta('Puntaje', `${num(c.puntaje)} / ${num(c.maximo)}`, 'primary'),
        );

        const textos = [];
        if (r.aviso) textos.push(r.aviso);
        if (r.revisar.length) textos.push(`Revisa a mano las preguntas ${r.revisar.join(', ')}: la marca no es del todo clara.`);
        if (c.multiples) textos.push(`${c.multiples} pregunta(s) con doble marca: cuentan como malas.`);
        $('avisoRevisar').textContent = textos.join(' ');
        $('avisoRevisar').hidden = !textos.length;

        const grid = $('gridRespuestas');
        grid.textContent = '';
        r.respuestas.forEach((resp, i) => {
            const clave = r.clave[i];
            let clase = 'escaner-celda';
            if (clave === '-') clase += ' anulada';
            else if (resp === '_') clase += ' blanco';
            else if (resp === '*') clase += ' multiple';
            else clase += resp === clave ? ' correcta' : ' incorrecta';
            if (r.revisar.includes(i + 1)) clase += ' revisar';
            const celda = nodo('div', clase);
            celda.append(nodo('span', 'escaner-num', String(i + 1)),
                nodo('span', 'escaner-letra', resp === '_' ? '–' : resp === '*' ? '✱' : resp));
            if (clave !== '-' && resp !== clave && resp !== '_') celda.append(nodo('span', 'escaner-clave', clave));
            celda.title = clave === '-' ? `Pregunta ${i + 1}: anulada` : `Pregunta ${i + 1}: marcó ${resp === '_' ? 'nada' : resp === '*' ? 'dos' : resp}, correcta ${clave}`;
            grid.append(celda);
        });

        $('imgCorregida').src = r.imagen || '';
        $('entFecha').value = $('entFecha').value || hoyLocal();
        $('entFecha').max = hoyLocal();
        $('entHora').focus();
    }

    function limpiarResultado() {
        escaneo = null;
        $('resultado').hidden = true;
        $('resultadoVacio').hidden = false;
        $('imgCorregida').removeAttribute('src');
        $('entHora').value = '';
        cam.limpiar();
    }

    $('btnDescartar').addEventListener('click', () => {
        limpiarResultado();
        aviso('Ficha descartada. Puedes volver a escanearla.', 'secondary');
    });

    // ---------------------------------------------------------------- guardar
    $('formGuardar').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        if (!escaneo) return;
        const btn = ev.submitter || $('formGuardar').querySelector('[type=submit]');
        btn.disabled = true;
        try {
            const r = await Api.post('examenes', 'guardar_resultado', {
                escaneo, fecha: $('entFecha').value, hora: $('entHora').value,
            });
            $('contEscaneados').textContent = r.escaneados;
            aviso(`Guardado: ${alumno.nombres} — ${r.correctas}/${r.total} buenas, ${num(r.puntaje)} puntos.`);
            // listo para el siguiente alumno (la fecha se conserva)
            limpiarResultado();
            alumno = null;
            $('tarjetaAlumno').hidden = true;
            $('ayudaPaso2').textContent = 'Primero busca al alumno por su código.';
            cam.habilitar(false);
            txtCodigo.value = '';
            txtCodigo.focus();
        } catch (e) {
            aviso(e.message, 'danger');
            if (/ya no está disponible/.test(e.message)) limpiarResultado();
        } finally {
            btn.disabled = false;
        }
    });

    window.addEventListener('beforeunload', (ev) => {
        if (escaneo) { ev.preventDefault(); ev.returnValue = ''; }     // ficha leída sin guardar
    });
})();
