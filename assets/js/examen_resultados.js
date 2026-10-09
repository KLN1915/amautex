/* Resultados de un examen: ranking, resumen y ficha de cada alumno. */
(() => {
    const tabla = document.getElementById('tablaResultados');
    if (!tabla) return;
    const num = (v) => String(Math.round(Number(v) * 100) / 100);
    let datos = null;

    function tarjeta(titulo, valor, color) {
        const col = nodo('div', 'col-6 col-md-3');
        const t = nodo('div', `escaner-dato border-${color}`);
        t.append(nodo('div', 'escaner-dato-valor', valor), nodo('div', 'escaner-dato-titulo', titulo));
        col.append(t);
        return col;
    }

    function fechaHora(s) {
        if (!s) return '—';
        const [f, h] = s.split(' ');
        const [a, m, d] = f.split('-');
        
        return `${d}/${m} ${h === '00:00:00' ? 'T. límite' : h.slice(0, 5)}`;
    }

    async function cargar() {
        try {
            datos = await Api.get('examenes', 'resultados_datos', { id: EXAMEN_ID });
        } catch (e) {
            tabla.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">${escapar(e.message)}</td></tr>`;
            return;
        }
        const { filas, reglas } = datos;
        console.log(filas)
        const r = (v) => (v > 0 ? '+' : '') + num(v);
        document.getElementById('reglasTexto').textContent =
            `Buena ${r(reglas.correcta)} · mala ${r(reglas.incorrecta)} · en blanco ${r(reglas.blanco)}`;

        const pts = filas.map((f) => Number(f.puntaje));
        const max = filas[0]?.maximo ?? 0;
        document.getElementById('resumenResultados').replaceChildren(
            tarjeta('Fichas', String(filas.length), 'primary'),
            tarjeta('Promedio', filas.length ? `${num(pts.reduce((a, b) => a + b, 0) / pts.length)} / ${num(max)}` : '—', 'success'),
            tarjeta('Más alto', filas.length ? num(Math.max(...pts)) : '—', 'warning'),
            tarjeta('Más bajo', filas.length ? num(Math.min(...pts)) : '—', 'secondary'),
        );

        if (!filas.length) {
            tabla.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-secondary">Todavía no hay fichas guardadas.</td></tr>';
            return;
        }
        tabla.innerHTML = filas.map((f) => `
            <tr>
                <td class="text-center fw-semibold">${f.puesto <= 3 ? ['🥇', '🥈', '🥉'][f.puesto - 1] : Number(f.puesto)}</td>
                <td>
                    <div class="fw-semibold">${escapar(f.alumno)}</div>
                    <small class="text-secondary">${escapar(f.codigo)} · ${escapar(f.grado_texto)}</small>
                    ${f.revisar.length ? `<span class="badge text-bg-warning ms-1" title="Preguntas con marca dudosa: ${escapar(f.revisar.join(', '))}">revisar ${f.revisar.length}</span>` : ''}
                </td>
                <td class="text-center text-success fw-semibold">${Number(f.correctas)}</td>
                <td class="text-center text-danger">${Number(f.incorrectas)}</td>
                <td class="text-center text-secondary">${Number(f.blancos)}</td>
                <td class="text-end"><span class="fw-bold">${escapar(num(f.puntaje))}</span>
                    <small class="text-secondary">/ ${escapar(num(f.maximo))}</small></td>
                <td class="small text-nowrap">${escapar(fechaHora(f.entregado_en))}</td>
                <td class="text-end text-nowrap">
                    <button class="btn btn-sm btn-light accion-ver" data-id="${Number(f.id)}" title="Ver ficha"><i class="bi bi-eye"></i></button>
                    ${PUEDE_EDITAR ? `<button class="btn btn-sm btn-light text-danger accion-eliminar" data-id="${Number(f.id)}"
                        data-nombre="${escapar(f.alumno)}" title="Eliminar"><i class="bi bi-trash"></i></button>` : ''}
                </td>
            </tr>`).join('');
    }

    // ---------------------------------------------------------------- ver ficha
    const modal = new bootstrap.Modal(document.getElementById('modalFicha'));
    function verFicha(f) {
        const clave = datos.examen.clave || '';
        document.getElementById('fichaTitulo').textContent = `${f.alumno} — puesto ${f.puesto}`;
        const img = document.getElementById('fichaImagen');
        img.hidden = !f.tiene_imagen;
        if (f.tiene_imagen) img.src = `${BASE}index.php?p=examenes&accion=hoja&id=${Number(f.id)}`;
        document.getElementById('fichaResumen').replaceChildren(
            tarjeta('Buenas', String(f.correctas), 'success'),
            tarjeta('Malas', String(f.incorrectas), 'danger'),
            tarjeta('En blanco', String(f.blancos), 'secondary'),
            tarjeta('Puntaje', `${num(f.puntaje)} / ${num(f.maximo)}`, 'primary'),
        );
        const grid = document.getElementById('fichaGrid');
        grid.textContent = '';
        [...f.respuestas].forEach((resp, i) => {
            const k = clave[i];
            let clase = 'escaner-celda';
            if (k === '-') clase += ' anulada';
            else if (resp === '_') clase += ' blanco';
            else if (resp === '*') clase += ' multiple';
            else clase += resp === k ? ' correcta' : ' incorrecta';
            if (f.revisar.includes(i + 1)) clase += ' revisar';
            const celda = nodo('div', clase);
            celda.append(nodo('span', 'escaner-num', String(i + 1)),
                nodo('span', 'escaner-letra', resp === '_' ? '–' : resp === '*' ? '✱' : resp));
            if (k && k !== '-' && resp !== k && resp !== '_') celda.append(nodo('span', 'escaner-clave', k));
            grid.append(celda);
        });
        modal.show();
    }

    tabla.addEventListener('click', async (ev) => {
        const ver = ev.target.closest('.accion-ver');
        const el = ev.target.closest('.accion-eliminar');
        if (ver) verFicha(datos.filas.find((f) => f.id === Number(ver.dataset.id)));
        if (el) {
            if (!confirmar(`¿Eliminar la ficha de ${el.dataset.nombre}? Podrás volver a escanearla.`)) return;
            try {
                await Api.post('examenes', 'eliminar_resultado', { id: el.dataset.id });
                aviso('Ficha eliminada.');
                cargar();
            } catch (e) {
                aviso(e.message, 'danger');
            }
        }
    });

    cargar();
})();
