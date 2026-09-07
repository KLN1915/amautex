/* Módulo Alumnos: consume index.php?p=alumnos&accion=... */
(() => {
    const tabla = document.getElementById('tablaAlumnos');
    if (!tabla) return;

    const modal    = new bootstrap.Modal('#modalAlumno');
    const form     = document.getElementById('formAlumno');
    const buscar   = document.getElementById('buscarAlumno');
    const fTipo    = document.getElementById('filtroTipo');
    const fColegio = document.getElementById('filtroColegio');
    const fNivel   = document.getElementById('filtroNivel');
    const fGrado   = document.getElementById('filtroGrado');
    const selCol   = document.getElementById('selColegio');
    const colNuevo = document.getElementById('colegioNuevo');
    const bloqueCol= document.getElementById('bloqueColegio');
    const ayudaCol = document.getElementById('ayudaColegio');
    let pagina = 1;

    const NUEVO = '__nuevo__';   // opción "agregar colegio" del combobox

    /* Catálogo COMPLETO de colegios, con su tipo. Se traen todos y no solo los
       del tipo elegido: si el usuario busca uno de otro tipo hay que mostrárselo
       y explicarle por qué no puede elegirlo, en vez de decir que no existe. */
    let colegiosCargados = [];

    const soles = (n) => 'S/ ' + Number(n).toFixed(2);

    /** Llena un <select> de grados según el nivel elegido. */
    function cargarGrados(select, nivel, valor = '', vacio = 'Todo grado') {
        const grados = NIVELES[nivel] || {};
        select.innerHTML = `<option value="">${vacio}</option>` +
            Object.entries(grados).map(([g, t]) =>
                `<option value="${g}" ${String(g) === String(valor) ? 'selected' : ''}>${t}</option>`).join('');
        select.disabled = Object.keys(grados).length === 0;
    }

    /** Tipo elegido en el formulario (estatal / particular / libre). */
    const tipoElegido = () => form.querySelector('input[name=tipo_alumno]:checked')?.value || 'estatal';

    // ---------------- Combobox de colegios ----------------
    /* Tom Select le pone buscador al <select>. Si la librería no cargara (CDN
       caído), todo sigue funcionando contra el <select> nativo. */
    const hayTomSelect = typeof TomSelect !== 'undefined';
    let ts = null;

    const nombreTipo = (t) => TIPOS[t]?.titulo || t;

    /** Opciones para el tipo elegido: los de otro tipo van visibles pero bloqueados. */
    function opcionesColegio(tipo) {
        const opciones = colegiosCargados.map(c => ({
            value: String(c.id),
            text: c.nombre,
            tipo: c.tipo,
            disabled: c.tipo !== tipo,
        }));
        // Los del tipo correcto primero; dentro de cada grupo, por nombre
        opciones.sort((a, b) =>
            (a.disabled - b.disabled) || a.text.localeCompare(b.text, 'es'));
        opciones.push({ value: NUEVO, text: '+ Agregar colegio nuevo…', tipo: '', disabled: false });
        return opciones;
    }

    function iniciarTomSelect() {
        if (ts || !hayTomSelect) return;
        ts = new TomSelect(selCol, {
            maxOptions: null,
            dropdownParent: 'body',   // si no, el desplegable queda cortado por el modal
            allowEmptyOption: true,
            render: {
                option(d, escape) {
                    if (d.value === NUEVO) {
                        return `<div class="text-primary fw-semibold">${escape(d.text)}</div>`;
                    }
                    if (d.disabled) {
                        return `<div>
                            <span class="text-secondary">${escape(d.text)}</span>
                            <span class="badge text-bg-light ms-1">${escape(d.tipo)}</span>
                            <small class="d-block text-secondary">
                                Cambia el tipo de alumno a «${escape(d.tipo)}» para poder elegirlo
                            </small>
                        </div>`;
                    }
                    return `<div>${escape(d.text)}</div>`;
                },
                item: (d, escape) => `<div>${escape(d.text)}</div>`,
                no_results: (d, escape) =>
                    `<div class="no-results p-2 text-secondary">
                        Ningún colegio se llama «${escape(d.input)}».
                        Usa <strong>«+ Agregar colegio nuevo»</strong> para registrarlo.
                     </div>`,
            },
        });
        ts.on('change', alCambiarColegio);
    }

    /** Vuelca las opciones del tipo actual en el combobox. */
    function pintarColegios(tipo, seleccionado = '') {
        const opciones = opcionesColegio(tipo);

        if (ts) {
            ts.clear(true);
            ts.clearOptions();
            ts.addOptions(opciones);
            ts.refreshOptions(false);
            if (seleccionado) ts.setValue(String(seleccionado), true);
            return;
        }

        selCol.innerHTML = '<option value="">— Elegir colegio —</option>' +
            opciones.map(o => `<option value="${o.value}"
                                       ${o.disabled ? 'disabled' : ''}
                                       ${o.value === String(seleccionado) ? 'selected' : ''}>
                                   ${escapar(o.text)}${o.disabled ? ' · ' + escapar(o.tipo) : ''}
                               </option>`).join('');
    }

    /**
     * Combobox de colegios. La lista se filtra por tipo a propósito: un alumno
     * particular no puede apuntar a un colegio estatal, y el servidor lo rechaza.
     * Pero los de otro tipo se muestran bloqueados en vez de esconderse, porque
     * si desaparecen al buscarlos parece que el colegio no existiera.
     */
    async function cargarColegios(tipo, seleccionado = '') {
        const pideColegio = TIPOS[tipo]?.colegio;
        bloqueCol.classList.toggle('d-none', !pideColegio);
        colNuevo.classList.add('d-none');
        colNuevo.value = '';
        // Alumno libre: sin colegio. Se limpia para no arrastrar el anterior.
        if (!pideColegio) { ts ? ts.clear(true) : (selCol.value = ''); return; }

        try {
            // Sin parámetro `tipo`: el catálogo entero, cada uno con el suyo
            if (!colegiosCargados.length) {
                colegiosCargados = await Api.get('alumnos', 'colegios');
            }
            iniciarTomSelect();
            pintarColegios(tipo, seleccionado);

            const propios = colegiosCargados.filter(c => c.tipo === tipo).length;
            const otros   = colegiosCargados.length - propios;
            ayudaCol.innerHTML =
                `<i class="bi bi-funnel me-1"></i>` +
                `<strong>${propios}</strong> colegio(s) ${escapar(nombreTipo(tipo).toLowerCase())}(es) disponibles` +
                (otros ? `, y ${otros} de otro tipo que aparecen en gris.` : '.') +
                ` Si no está, usa «Agregar colegio nuevo».`;
        } catch (e) {
            aviso(e.message, 'danger');
        }
    }

    /** Al elegir «Agregar colegio nuevo» se descubre el campo de texto. */
    function alCambiarColegio() {
        const nuevo = selCol.value === NUEVO;
        colNuevo.classList.toggle('d-none', !nuevo);
        if (nuevo) {
            // Lo tecleado en el buscador suele ser el nombre que no estaba
            if (!colNuevo.value && ts?.lastQuery) colNuevo.value = ts.lastQuery.trim();
            colNuevo.focus();
        } else {
            colNuevo.value = '';
        }
    }

    // ---------------- Listado ----------------
    async function listar() {
        tabla.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-secondary">Cargando…</td></tr>';
        try {
            const d = await Api.get('alumnos', 'listar', {
                q: buscar.value, tipo: fTipo.value, colegio: fColegio.value,
                nivel: fNivel.value, grado: fGrado.value, pagina
            });
            pintar(d);
        } catch (e) {
            tabla.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">${escapar(e.message)}</td></tr>`;
        }
    }

    function pintar(d) {
        if (!d.filas.length) {
            tabla.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-secondary">Sin alumnos con esos criterios.</td></tr>';
        } else {
            tabla.innerHTML = d.filas.map(a => `
                <tr>
                    <td><code>${escapar(a.codigo)}</code></td>
                    <td class="fw-semibold">${escapar(a.nombre_completo)}</td>
                    <td>
                        ${a.colegio ? escapar(a.colegio) : '<span class="text-secondary">Sin colegio</span>'}
                        <br><span class="badge text-bg-light">${escapar(a.tipo_texto)}</span>
                    </td>
                    <td><span class="badge text-bg-light">${escapar(a.grado_texto)}</span></td>
                    <td class="text-end fw-semibold">${soles(a.cuota)}</td>
                    <td>
                        <span class="badge text-bg-${a.activo ? 'success' : 'secondary'} ${PUEDE_EDITAR ? 'accion-estado' : ''}"
                              ${PUEDE_EDITAR ? `role="button" data-id="${a.id}" data-activo="${a.activo ? 0 : 1}"` : ''}>
                            ${a.activo ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        ${PUEDE_EDITAR ? `
                        <button class="btn btn-sm btn-light accion-editar" data-id="${a.id}" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-light text-danger accion-eliminar"
                                data-id="${a.id}" data-nombre="${escapar(a.nombre_completo)}" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>` : '<span class="text-secondary small">—</span>'}
                    </td>
                </tr>`).join('');
        }

        document.getElementById('totalAlumnos').textContent = `${d.total} alumno(s)`;
        const nav = document.getElementById('paginacionAlumnos');
        nav.innerHTML = '';
        for (let i = 1; i <= d.paginas; i++) {
            nav.insertAdjacentHTML('beforeend',
                `<li class="page-item ${i === d.pagina ? 'active' : ''}">
                    <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                 </li>`);
        }
    }

    // ---------------- Paneles de abajo ----------------
    /**
     * Repinta lo que vive fuera de la tabla y también cambia al guardar o
     * borrar: resumen por grado, cuotas, contador del plan y el catálogo del
     * filtro de colegios. Antes esto solo se armaba en PHP al cargar la
     * página, y por eso había que recargar para verlo.
     */
    async function refrescarPanel() {
        let d;
        try {
            d = await Api.get('alumnos', 'panel');
        } catch {
            return;   // el panel es informativo: si falla, no rompemos la pantalla
        }

        const res = document.getElementById('panelResumen');
        res.innerHTML = d.resumen.length
            ? d.resumen.map(r => `<span class="pastilla-resumen">
                                      ${escapar(r.texto)} <strong>${r.total}</strong>
                                  </span>`).join('')
            : '<span class="text-secondary small">Todavía no hay alumnos registrados.</span>';

        document.getElementById('panelCuotas').innerHTML = d.cuotas.map(c => `
            <dt>${escapar(c.titulo)}
                <span class="text-secondary">· ${c.alumnos} alumno(s)</span>
            </dt>
            <dd class="fw-semibold">${soles(c.tarifa)}</dd>`).join('');

        document.getElementById('cupoAlumnos').textContent =
            `${d.cupo.usados} / ${d.cupo.limite} del plan`;

        document.getElementById('avisoCupo').innerHTML =
            (d.cupo.limite > 0 && d.cupo.usados >= d.cupo.limite)
                ? `<div class="alert alert-warning">
                       <i class="bi bi-exclamation-triangle me-1"></i>
                       La academia alcanzó el límite de <strong>${d.cupo.limite} alumnos</strong>
                       de su plan. Para registrar más, hay que ampliar el plan.
                   </div>`
                : '';

        // El catálogo del modal también: si se acaba de crear un colegio al
        // vuelo, tiene que estar disponible la próxima vez que se abra.
        colegiosCargados = d.colegios;

        // El filtro de colegios se rearma para que aparezcan los recién creados
        const elegido = fColegio.value;
        fColegio.innerHTML = '<option value="">Todo colegio</option>' +
            d.colegios.map(c => `<option value="${c.id}" ${String(c.id) === elegido ? 'selected' : ''}>
                                     ${escapar(c.nombre)}
                                 </option>`).join('');
        // Si el colegio filtrado ya no existe, volvemos a "Todo colegio"
        if (elegido && fColegio.value !== elegido) {
            fColegio.value = '';
            pagina = 1;
            listar();
        }
    }

    /** Tras crear, editar o borrar cambian la tabla y los paneles. */
    async function recargarTodo() {
        await listar();
        refrescarPanel();
    }

    // ---------------- Acciones ----------------
    tabla.addEventListener('click', async (ev) => {
        const editar   = ev.target.closest('.accion-editar');
        const eliminar = ev.target.closest('.accion-eliminar');
        const estado   = ev.target.closest('.accion-estado');
        try {
            if (editar) {
                const a = await Api.get('alumnos', 'obtener', { id: editar.dataset.id });
                abrirModal(a);
            } else if (eliminar) {
                if (!confirmar(`¿Eliminar a ${eliminar.dataset.nombre}?`)) return;
                await Api.post('alumnos', 'eliminar', { id: eliminar.dataset.id });
                aviso('Alumno eliminado.');
                recargarTodo();
            } else if (estado) {
                await Api.post('alumnos', 'estado', { id: estado.dataset.id, activo: estado.dataset.activo });
                recargarTodo();   // el resumen y las cuotas solo cuentan activos
            }
        } catch (e) {
            aviso(e.message, 'danger');
        }
    });

    document.getElementById('paginacionAlumnos').addEventListener('click', (ev) => {
        const a = ev.target.closest('a[data-pagina]');
        if (!a) return;
        ev.preventDefault();
        pagina = Number(a.dataset.pagina);
        listar();
    });

    // ---------------- Alta / edición ----------------
    async function abrirModal(a = null) {
        form.reset();
        form.id.value = a?.id ?? 0;
        document.querySelector('#modalAlumno .modal-title').textContent = a ? 'Editar alumno' : 'Nuevo alumno';

        if (a) {
            ['codigo', 'apellido_paterno', 'apellido_materno', 'nombres']
                .forEach(c => { if (form[c]) form[c].value = a[c] ?? ''; });
            form.querySelector(`input[name=tipo_alumno][value="${a.tipo_alumno}"]`).checked = true;
            form.nivel.value = a.nivel;
            cargarGrados(form.grado, a.nivel, a.grado ?? '', 'Sin grado');
            form.activo.checked = Number(a.activo) === 1;
            await cargarColegios(a.tipo_alumno, a.colegio_id ?? '');
        } else {
            form.querySelector('input[name=tipo_alumno][value=estatal]').checked = true;
            form.nivel.value = 'secundaria';
            cargarGrados(form.grado, 'secundaria', '', 'Sin grado');
            form.activo.checked = true;
            await cargarColegios('estatal');
            try {
                const d = await Api.get('alumnos', 'siguiente_codigo');
                form.codigo.value = d.codigo;
            } catch { /* si falla, el servidor lo genera al guardar */ }
        }
        modal.show();
    }

    // Cambiar Estatal / Particular / Libre recarga el combobox de colegios
    document.getElementById('selectorTipo').addEventListener('change', () => {
        cargarColegios(tipoElegido());
    });

    // Con Tom Select el cambio lo emite la librería; sin ella, el select nativo
    if (!hayTomSelect) selCol.addEventListener('change', alCambiarColegio);

    document.getElementById('selNivel').addEventListener('change', (ev) => {
        cargarGrados(form.grado, ev.target.value, '', 'Sin grado');
    });

    // El botón no existe para el rol `consulta`: la vista no lo imprime.
    document.getElementById('btnNuevoAlumno')?.addEventListener('click', () => abrirModal());

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        try {
            const datos = new FormData(form);
            // El servidor espera un id o un nombre nuevo, nunca el marcador
            if (datos.get('colegio_id') === NUEVO) datos.set('colegio_id', '');
            await Api.post('alumnos', 'guardar', datos);
            modal.hide();
            aviso('Alumno guardado.');
            recargarTodo();
        } catch (e) {
            aviso(e.message, 'danger');
        } finally {
            btn.disabled = false;
        }
    });

    // ---------------- Filtros ----------------
    fTipo.addEventListener('change',    () => { pagina = 1; listar(); });
    fColegio.addEventListener('change', () => { pagina = 1; listar(); });
    fNivel.addEventListener('change', () => {
        cargarGrados(fGrado, fNivel.value);
        pagina = 1;
        listar();
    });
    fGrado.addEventListener('change', () => { pagina = 1; listar(); });
    buscar.addEventListener('input', esperar(() => { pagina = 1; listar(); }));

    cargarGrados(fGrado, '');
    listar();
})();
