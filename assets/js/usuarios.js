/* Módulo Usuarios: consume index.php?p=usuarios&accion=... */
(() => {
    const tabla = document.getElementById('tablaUsuarios');
    if (!tabla) return;

    const modal  = new bootstrap.Modal('#modalUsuario');
    const form   = document.getElementById('formUsuario');
    const buscar = document.getElementById('buscarUsuario');
    const GLOBAL = tabla.dataset.global === '1';   // superadmin sin academia seleccionada
    const COLS   = GLOBAL ? 7 : 6;
    let pagina = 1;

    const ROLES = { admin: 'Administrador', operador: 'Operador', consulta: 'Consulta' };

    async function listar() {
        tabla.innerHTML = `<tr><td colspan="${COLS}" class="text-center py-4 text-secondary">Cargando…</td></tr>`;
        try {
            const d = await Api.get('usuarios', 'listar', { q: buscar.value, pagina });
            pintar(d);
        } catch (e) {
            tabla.innerHTML = `<tr><td colspan="${COLS}" class="text-center py-4 text-danger">${escapar(e.message)}</td></tr>`;
        }
    }

    function pintar(d) {
        if (!d.filas.length) {
            tabla.innerHTML = `<tr><td colspan="${COLS}" class="text-center py-4 text-secondary">Sin resultados.</td></tr>`;
        } else {
            tabla.innerHTML = d.filas.map(u => `
                <tr>
                    <td>${escapar(u.nombre)}</td>
                    <td><code>${escapar(u.usuario)}</code></td>
                    ${GLOBAL ? `<td><span class="badge text-bg-light">${escapar(u.academia || '—')}</span></td>` : ''}
                    <td>${escapar(u.correo || '—')}</td>
                    <td><span class="badge text-bg-light">${escapar(ROLES[u.rol] || u.rol)}</span></td>
                    <td>
                        <span class="badge text-bg-${u.activo ? 'success' : 'secondary'} accion-estado"
                              role="button" data-id="${u.id}" data-activo="${u.activo ? 0 : 1}">
                            ${u.activo ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td class="text-end ${GLOBAL ? 'd-none' : ''}">
                        <button class="btn btn-sm btn-light accion-editar" data-id="${u.id}" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-light text-danger accion-eliminar"
                                data-id="${u.id}" data-nombre="${escapar(u.nombre)}" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>`).join('');
        }

        document.getElementById('totalUsuarios').textContent = `${d.total} usuario(s)`;

        const nav = document.getElementById('paginacionUsuarios');
        nav.innerHTML = '';
        for (let i = 1; i <= d.paginas; i++) {
            nav.insertAdjacentHTML('beforeend',
                `<li class="page-item ${i === d.pagina ? 'active' : ''}">
                    <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                 </li>`);
        }
    }

    // ---- Eventos delegados sobre la tabla ----
    tabla.addEventListener('click', async (ev) => {
        const editar   = ev.target.closest('.accion-editar');
        const eliminar = ev.target.closest('.accion-eliminar');
        const estado   = ev.target.closest('.accion-estado');
        try {
            if (editar) {
                const u = await Api.get('usuarios', 'obtener', { id: editar.dataset.id });
                abrirModal(u);
            } else if (eliminar) {
                if (!confirmar(`¿Eliminar a ${eliminar.dataset.nombre}?`)) return;
                await Api.post('usuarios', 'eliminar', { id: eliminar.dataset.id });
                aviso('Usuario eliminado.');
                listar();
            } else if (estado) {
                await Api.post('usuarios', 'estado', { id: estado.dataset.id, activo: estado.dataset.activo });
                listar();
            }
        } catch (e) {
            aviso(e.message, 'danger');
        }
    });

    document.getElementById('paginacionUsuarios').addEventListener('click', (ev) => {
        const a = ev.target.closest('a[data-pagina]');
        if (!a) return;
        ev.preventDefault();
        pagina = Number(a.dataset.pagina);
        listar();
    });

    // ---- Alta / edición ----
    function abrirModal(u = null) {
        form.reset();
        form.id.value = u?.id ?? 0;
        document.querySelector('#modalUsuario .modal-title').textContent = u ? 'Editar usuario' : 'Nuevo usuario';
        if (u) {
            form.nombre.value   = u.nombre;
            form.usuario.value  = u.usuario;
            form.correo.value   = u.correo || '';
            form.rol.value      = u.rol;
            form.activo.checked = Number(u.activo) === 1;
        } else {
            form.activo.checked = true;
        }
        modal.show();
    }

    document.getElementById('btnNuevoUsuario')?.addEventListener('click', () => abrirModal());

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        try {
            await Api.post('usuarios', 'guardar', new FormData(form));
            modal.hide();
            aviso('Guardado correctamente.');
            listar();
        } catch (e) {
            aviso(e.message, 'danger');
        } finally {
            btn.disabled = false;
        }
    });

    buscar.addEventListener('input', esperar(() => { pagina = 1; listar(); }));

    listar();
})();
