/* Configurar las respuestas correctas de un examen. */
(() => {
    const grid = document.getElementById('gridClave');
    if (!grid) return;
    const form = document.getElementById('formClave');
    const progreso = document.getElementById('progresoClave');
    const N = EXAMEN.preguntas;
    const OPCIONES = [...LETRAS, '-'];

    // ---------- una fila por pregunta: número + alternativas + anular
    for (let n = 1; n <= N; n++) {
        const fila = nodo('div', 'clave-fila');
        fila.dataset.n = n;
        fila.append(nodo('span', 'clave-num', `${n}.`));
        OPCIONES.forEach((op) => {
            const id = `p${n}_${op === '-' ? 'x' : op}`;
            const input = nodo('input', 'btn-check');
            Object.assign(input, { type: 'radio', name: `p${n}`, id, value: op, autocomplete: 'off' });
            const label = nodo('label', 'btn btn-sm ' + (op === '-' ? 'btn-outline-secondary clave-anular' : 'btn-outline-primary'),
                op === '-' ? '–' : op);
            label.htmlFor = id;
            if (op === '-') label.title = 'Anular la pregunta';
            fila.append(input, label);
        });
        grid.append(fila);
    }

    const elegir = (n, valor) => {
        const r = form.querySelector(`input[name="p${n}"][value="${valor}"]`);
        if (r) r.checked = true;
    };
    const valorDe = (n) => form.querySelector(`input[name="p${n}"]:checked`)?.value ?? '';

    function actualizar() {
        let listas = 0;
        for (let n = 1; n <= N; n++) {
            const v = valorDe(n);
            const fila = grid.children[n - 1];
            if (v) { listas++; fila.classList.remove('pendiente'); }
        }
        progreso.textContent = `${listas} / ${N} con respuesta`;
        progreso.className = 'small ' + (listas === N ? 'text-success fw-semibold' : 'text-secondary');
    }
    grid.addEventListener('change', actualizar);

    // clave ya guardada
    if (EXAMEN.clave) [...EXAMEN.clave].forEach((v, i) => elegir(i + 1, v));
    actualizar();

    // ---------- proponer desde una hoja escaneada
    Camara.iniciar({
        alFoto: async (blob) => {
            const datos = new FormData();
            datos.append('id', EXAMEN.id);
            datos.append('imagen', blob, 'clave.jpg');
            try {
                const r = await Api.post('examenes', 'leer_clave', datos);
                if (EXAMEN.clave && r.marcadas && !confirmar('¿Reemplazar las respuestas actuales por las de la hoja escaneada?')) return;
                r.propuesta.forEach((p) => {
                    const fila = grid.children[p.n - 1];
                    if (p.respuesta) elegir(p.n, p.respuesta);
                    else form.querySelectorAll(`input[name="p${p.n}"]`).forEach((x) => { x.checked = false; });
                    fila.classList.toggle('pendiente', !p.respuesta || p.revisar);
                });
                actualizar();
                aviso(r.marcadas
                    ? `Se propusieron ${r.marcadas} respuestas. Revisa las resaltadas y guarda.`
                    : 'Hoja en blanco: elige la respuesta correcta de cada pregunta.', r.marcadas ? 'success' : 'info');
            } catch (e) {
                aviso(e.message, 'danger');
            }
        },
    });

    document.getElementById('btnLimpiar').addEventListener('click', () => {
        if (!confirmar('¿Quitar todas las respuestas elegidas?')) return;
        form.querySelectorAll('input.btn-check').forEach((x) => { x.checked = false; });
        actualizar();
    });

    // ---------- guardar
    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const faltan = [];
        let clave = '';
        for (let n = 1; n <= N; n++) {
            const v = valorDe(n);
            if (!v) { faltan.push(n); grid.children[n - 1].classList.add('pendiente'); }
            clave += v || '?';
        }
        if (faltan.length) {
            grid.children[faltan[0] - 1].scrollIntoView({ behavior: 'smooth', block: 'center' });
            aviso(`Falta elegir la respuesta de ${faltan.length} pregunta(s): ${faltan.slice(0, 12).join(', ')}${faltan.length > 12 ? '…' : ''}.`, 'warning');
            return;
        }
        if (EXAMEN.escaneados && !confirmar(`Este examen ya tiene ${EXAMEN.escaneados} ficha(s) escaneadas. `
            + 'Sus puntajes se recalcularán con las nuevas respuestas. ¿Continuar?')) return;

        const btn = form.querySelector('[type=submit]');
        btn.disabled = true;
        try {
            const r = await Api.post('examenes', 'guardar_clave', { id: EXAMEN.id, clave });
            EXAMEN.clave = clave;
            document.getElementById('btnIrEscanear').classList.remove('d-none');
            aviso(r.recalculadas
                ? `Respuestas guardadas. Se recalcularon ${r.recalculadas} ficha(s).`
                : 'Respuestas guardadas. Ya puedes escanear las fichas de los alumnos.');
        } catch (e) {
            aviso(e.message, 'danger');
        } finally {
            btn.disabled = false;
        }
    });
})();
