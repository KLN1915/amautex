/* Módulo Exámenes: lista, alta/edición y reglas de puntaje de la academia. */
(() => {
    const tabla = document.getElementById('tablaExamenes');
    if (!tabla) return;
    const url = (accion, id) => `${BASE}index.php?p=examenes&accion=${accion}&id=${id}`;
    let abrirModal = () => {};          // se define abajo solo si el usuario puede editar

    // ---------------------------------------------------------------- lista
    async function listar() {
        let filas;
        try {
            filas = await Api.get('examenes', 'listar');
        } catch (e) {
            tabla.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${escapar(e.message)}</td></tr>`;
            return;
        }
        if (!filas.length) {
            tabla.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-secondary">
                Aún no hay exámenes.${PUEDE_EDITAR ? ' Crea el primero con «Nuevo examen».' : ''}</td></tr>`;
            return;
        }
        tabla.innerHTML = filas.map((x) => `
            <tr>
                <td>
                    <div class="fw-semibold">${escapar(x.nombre)}</div>
                    <small class="text-secondary">${x.fecha ? escapar(fechaCorta(x.fecha)) : 'sin fecha'} · hoja ${escapar(x.plantilla)}</small>
                </td>
                <td><span class="badge text-bg-light">${escapar(x.grado_texto)}</span></td>
                <td class="text-center">${Number(x.preguntas)}</td>
                <td class="text-center">${x.tiene_clave
                    ? '<span class="badge text-bg-success"><i class="bi bi-check-lg"></i> configuradas</span>'
                    : '<span class="badge text-bg-warning">pendiente</span>'}</td>
                <td class="text-center">${Number(x.resultados)}</td>
                <td class="text-end text-nowrap">
                    ${PUEDE_EDITAR ? `
                    <a class="btn btn-sm ${x.tiene_clave ? 'btn-light' : 'btn-warning'}" href="${url('clave', x.id)}" title="Configurar respuestas correctas">
                        <i class="bi bi-key"></i><span class="d-none d-xl-inline ms-1">Respuestas</span></a>
                    <a class="btn btn-sm btn-primary ${x.tiene_clave ? '' : 'disabled'}" href="${url('escanear', x.id)}" title="Escanear fichas de alumnos">
                        <i class="bi bi-camera"></i><span class="d-none d-xl-inline ms-1">Escanear</span></a>` : ''}
                    <a class="btn btn-sm btn-light" href="${url('resultados', x.id)}" title="Resultados">
                        <i class="bi bi-trophy"></i><span class="d-none d-xl-inline ms-1">Resultados</span></a>
                    ${PUEDE_EDITAR ? `<button class="btn btn-sm btn-light accion-editar" data-id="${Number(x.id)}" title="Editar"><i class="bi bi-pencil"></i></button>` : ''}
                    ${PUEDE_BORRAR ? `<button class="btn btn-sm btn-light text-danger accion-eliminar" data-id="${Number(x.id)}"
                        data-nombre="${escapar(x.nombre)}" data-fichas="${Number(x.resultados)}" title="Eliminar"><i class="bi bi-trash"></i></button>` : ''}
                </td>
            </tr>`).join('');
    }

    function fechaCorta(iso) {
        const [a, m, d] = String(iso).split('-');
        return `${d}/${m}/${a}`;
    }

    tabla.addEventListener('click', async (ev) => {
        const ed = ev.target.closest('.accion-editar');
        const el = ev.target.closest('.accion-eliminar');
        try {
            if (ed) abrirModal(await Api.get('examenes', 'obtener', { id: ed.dataset.id }));
            if (el) {
                const fichas = Number(el.dataset.fichas);
                if (!confirmar(`¿Eliminar el examen «${el.dataset.nombre}»?` +
                    (fichas ? `\nSe borrarán también las ${fichas} ficha(s) escaneadas.` : ''))) return;
                await Api.post('examenes', 'eliminar', { id: el.dataset.id });
                aviso('Examen eliminado.');
                listar();
            }
        } catch (e) {
            aviso(e.message, 'danger');
        }
    });

    // ---------------------------------------------------------------- alta / edición
    const form = document.getElementById('formExamen');
    if (form) {
        const modal = new bootstrap.Modal(document.getElementById('modalExamen'));
        const selNivel = form.nivel, selGrado = form.grado, selHoja = form.plantilla;
        let hojasCargadas = false;

        function cargarGrados(valor = '') {
            const grados = NIVELES[selNivel.value] || {};
            selGrado.innerHTML = '<option value="">Todos los grados</option>' +
                Object.entries(grados).map(([g, t]) => `<option value="${escapar(g)}">${escapar(t)}</option>`).join('');
            selGrado.value = valor === null ? '' : String(valor);
        }
        selNivel.addEventListener('change', () => cargarGrados());

        async function cargarHojas(elegida) {
            if (!hojasCargadas) {
                try {
                    const lista = await Api.get('examenes', 'plantillas');
                    selHoja.innerHTML = lista.map((p) =>
                        `<option value="${escapar(p.nombre)}">${escapar(p.nombre)} — ${Number(p.preguntas)} preguntas, ${Number(p.opciones)} alternativas</option>`).join('');
                    hojasCargadas = true;
                } catch (e) {
                    aviso('No se pudo consultar el lector de fichas: ' + e.message, 'warning');
                }
            }
            if (elegida) selHoja.value = elegida;
        }

        abrirModal = (x = null) => {
            form.reset();
            form.id.value = x?.id ?? 0;
            form.querySelector('.modal-title').textContent = x ? 'Editar examen' : 'Nuevo examen';
            form.nombre.value = x?.nombre ?? '';
            form.fecha.value = x?.fecha ?? hoyLocal();
            selNivel.value = x?.nivel ?? 'secundaria';
            cargarGrados(x?.grado ?? '');
            cargarHojas(x?.plantilla);
            modal.show();
            setTimeout(() => form.nombre.focus(), 300);
        };

        document.getElementById('btnNuevoExamen')?.addEventListener('click', () => abrirModal());
        form.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const btn = form.querySelector('[type=submit]');
            btn.disabled = true;
            try {
                const nuevo = Number(form.id.value) === 0;
                const r = await Api.post('examenes', 'guardar', new FormData(form));
                modal.hide();
                aviso(nuevo ? 'Examen creado. Ahora configura sus respuestas.' : 'Examen actualizado.');
                if (nuevo) { location.href = url('clave', r.id); return; }
                listar();
            } catch (e) {
                aviso(e.message, 'danger');
            } finally {
                btn.disabled = false;
            }
        });
    }
    // ---------------------------------------------------------------- reglas de puntaje
    const fr = document.getElementById('formReglas');
    const ejemplo = document.getElementById('ejemploReglas');
    function pintarEjemplo() {
        const c = Number(fr.correcta.value), i = Number(fr.incorrecta.value), b = Number(fr.blanco.value);
        const n = (v) => (v > 0 ? '+' : '') + v;
        ejemplo.textContent = `Ejemplo con 60 preguntas: 45 buenas, 10 malas y 5 en blanco = `
            + `45×${n(c)} + 10×${n(i)} + 5×${n(b)} = ${(45 * c + 10 * i + 5 * b).toFixed(2).replace(/\.00$/, '')} `
            + `de ${(60 * c).toFixed(2).replace(/\.00$/, '')} puntos.`;
    }
    fr.addEventListener('input', pintarEjemplo);
    pintarEjemplo();
    fr.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const btn = fr.querySelector('[type=submit]');
        if (!btn) return;
        btn.disabled = true;
        try {
            await Api.post('examenes', 'guardar_reglas', new FormData(fr));
            aviso('Puntaje guardado. Todos los resultados ya usan estos puntos.');
        } catch (e) {
            aviso(e.message, 'danger');
        } finally {
            btn.disabled = false;
        }
    });

    listar();
})();
