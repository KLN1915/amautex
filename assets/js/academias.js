/* Módulo Academias (superadmin): alta de clientes, plan, estado y credenciales. */
(() => {
    const tabla = document.getElementById('tablaAcademias');
    if (!tabla) return;

    const modal       = new bootstrap.Modal('#modalAcademia');
    const modalCred   = new bootstrap.Modal('#modalCredenciales');
    const form        = document.getElementById('formAcademia');
    const buscar      = document.getElementById('buscarAcademia');
    const filtro      = document.getElementById('filtroEstado');
    let pagina = 1;
    let academiaCred = 0;   // academia abierta en el modal de credenciales

    const PLANES = { prueba: 'Prueba', basico: 'Básico', pro: 'Pro' };

    // ---------------- Listado ----------------
    async function listar() {
        tabla.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-secondary">Cargando…</td></tr>';
        try {
            const d = await Api.get('academias', 'listar', { q: buscar.value, estado: filtro.value, pagina });
            pintar(d);
        } catch (e) {
            tabla.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">${escapar(e.message)}</td></tr>`;
        }
    }

    function pintar(d) {
        if (!d.filas.length) {
            tabla.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-secondary">Sin resultados.</td></tr>';
        } else {
            tabla.innerHTML = d.filas.map(a => {
                const dias = a.dias_restantes === null ? null : Number(a.dias_restantes);
                const venc = a.fin_plan
                    ? `${formatoFecha(a.fin_plan)}<br><span class="badge text-bg-${dias < 0 ? 'danger' : (dias <= 15 ? 'warning' : 'success')}">
                           ${dias < 0 ? 'vencida' : dias + ' días'}</span>`
                    : '<span class="text-secondary">sin vencimiento</span>';

                return `
                <tr>
                    <td>
                        <div class="fw-semibold">
                            <span class="punto-color" style="background:${escapar(a.color)}"></span>
                            ${escapar(a.nombre)}
                        </div>
                        <small class="text-secondary">${escapar(a.codigo)}${a.ruc ? ' · RUC ' + escapar(a.ruc) : ''}</small>
                    </td>
                    <td><span class="badge text-bg-light">${escapar(PLANES[a.plan] || a.plan)}</span></td>
                    <td>${venc}</td>
                    <td class="text-center">${a.usuarios} / ${a.limite_usuarios}</td>
                    <td class="text-center">${a.alumnos} / ${a.limite_alumnos}</td>
                    <td>
                        <span class="badge text-bg-${a.estado === 'activa' ? 'success' : 'secondary'} accion-estado"
                              role="button" data-id="${a.id}" data-estado="${a.estado === 'activa' ? 'suspendida' : 'activa'}">
                            ${a.estado === 'activa' ? 'Activa' : 'Suspendida'}
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-light accion-entrar" data-id="${a.id}" title="Entrar a esta academia">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </button>
                        <button class="btn btn-sm btn-light accion-credenciales" data-id="${a.id}" title="Credenciales">
                            <i class="bi bi-key"></i>
                        </button>
                        <button class="btn btn-sm btn-light accion-renovar" data-id="${a.id}" title="Renovar plan">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                        <button class="btn btn-sm btn-light accion-editar" data-id="${a.id}" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-light text-danger accion-eliminar"
                                data-id="${a.id}" data-nombre="${escapar(a.nombre)}" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
        }

        document.getElementById('totalAcademias').textContent = `${d.total} academia(s)`;
        const nav = document.getElementById('paginacionAcademias');
        nav.innerHTML = '';
        for (let i = 1; i <= d.paginas; i++) {
            nav.insertAdjacentHTML('beforeend',
                `<li class="page-item ${i === d.pagina ? 'active' : ''}">
                    <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                 </li>`);
        }
    }

    function formatoFecha(f) {
        if (!f) return '';
        const [a, m, d] = f.split('-');
        return `${d}/${m}/${a}`;
    }

    // ---------------- Acciones de la tabla ----------------
    tabla.addEventListener('click', async (ev) => {
        const btn = (c) => ev.target.closest(c);
        try {
            if (btn('.accion-editar')) {
                const a = await Api.get('academias', 'obtener', { id: btn('.accion-editar').dataset.id });
                abrirModal(a);

            } else if (btn('.accion-estado')) {
                const el = btn('.accion-estado');
                await Api.post('academias', 'estado', { id: el.dataset.id, estado: el.dataset.estado });
                aviso(el.dataset.estado === 'activa' ? 'Academia activada.' : 'Academia suspendida.');
                listar();

            } else if (btn('.accion-renovar')) {
                const meses = prompt('¿Cuántos meses renovar el alquiler?', '1');
                if (meses === null) return;
                const d = await Api.post('academias', 'renovar', { id: btn('.accion-renovar').dataset.id, meses });
                aviso('Plan renovado hasta ' + formatoFecha(d.fin_plan));
                listar();

            } else if (btn('.accion-credenciales')) {
                abrirCredenciales(Number(btn('.accion-credenciales').dataset.id));

            } else if (btn('.accion-entrar')) {
                await Api.post('academias', 'acceder', { id: btn('.accion-entrar').dataset.id });
                location.href = `${BASE}index.php?p=dashboard`;

            } else if (btn('.accion-eliminar')) {
                const el = btn('.accion-eliminar');
                if (!confirmar(`Se eliminará "${el.dataset.nombre}" con TODOS sus usuarios y alumnos. ¿Continuar?`)) return;
                await Api.post('academias', 'eliminar', { id: el.dataset.id });
                aviso('Academia eliminada.');
                listar();
            }
        } catch (e) {
            aviso(e.message, 'danger');
        }
    });

    document.getElementById('paginacionAcademias').addEventListener('click', (ev) => {
        const a = ev.target.closest('a[data-pagina]');
        if (!a) return;
        ev.preventDefault();
        pagina = Number(a.dataset.pagina);
        listar();
    });

    // ---------------- Alta / edición ----------------
    function abrirModal(a = null) {
        form.reset();
        form.id.value = a?.id ?? 0;
        document.querySelector('#modalAcademia .modal-title').textContent = a ? 'Editar academia' : 'Nueva academia';
        // El administrador solo se pide al crear la academia
        document.getElementById('bloqueAdmin').classList.toggle('d-none', !!a);

        if (a) {
            ['nombre', 'codigo', 'ruc', 'correo', 'telefono', 'direccion', 'color',
             'plan', 'estado', 'limite_usuarios', 'limite_alumnos', 'inicio_plan', 'fin_plan']
                .forEach(c => { if (form[c]) form[c].value = a[c] ?? ''; });
            if (!a.color) form.color.value = '#2563eb';
        }
        modal.show();
    }

    document.getElementById('btnNuevaAcademia').addEventListener('click', () => abrirModal());

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        try {
            const d = await Api.post('academias', 'guardar', new FormData(form));
            modal.hide();
            listar();
            if (d.clave) {
                // Alta nueva: mostrar las credenciales del administrador creado
                mostrarClave(d.clave, `Usuario: ${d.usuario}`);
                modalCred.show();
                document.getElementById('tablaCredenciales').innerHTML =
                    `<tr><td colspan="5" class="text-secondary">Academia creada. Entrega estas credenciales al cliente.</td></tr>`;
            } else {
                aviso('Academia actualizada.');
            }
        } catch (e) {
            aviso(e.message, 'danger');
        } finally {
            btn.disabled = false;
        }
    });

    // ---------------- Credenciales ----------------
    async function abrirCredenciales(id) {
        academiaCred = id;
        document.getElementById('credencialesNueva').classList.add('d-none');
        const cuerpo = document.getElementById('tablaCredenciales');
        cuerpo.innerHTML = '<tr><td colspan="5" class="text-secondary">Cargando…</td></tr>';
        modalCred.show();
        try {
            const d = await Api.get('academias', 'credenciales', { id });
            document.querySelector('#modalCredenciales .modal-title').textContent = 'Credenciales · ' + d.academia.nombre;
            cuerpo.innerHTML = d.usuarios.length ? d.usuarios.map(u => `
                <tr>
                    <td>${escapar(u.nombre)}</td>
                    <td><code>${escapar(u.usuario)}</code></td>
                    <td><span class="badge text-bg-light">${escapar(u.rol)}</span>
                        ${Number(u.activo) ? '' : '<span class="badge text-bg-secondary ms-1">inactivo</span>'}</td>
                    <td>${u.ultimo_acceso ? escapar(u.ultimo_acceso) : '<span class="text-secondary">nunca</span>'}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary accion-reset" data-id="${u.id}">
                            <i class="bi bi-arrow-clockwise"></i> Restablecer
                        </button>
                    </td>
                </tr>`).join('')
                : '<tr><td colspan="5" class="text-secondary">Esta academia no tiene usuarios.</td></tr>';
        } catch (e) {
            cuerpo.innerHTML = `<tr><td colspan="5" class="text-danger">${escapar(e.message)}</td></tr>`;
        }
    }

    document.getElementById('tablaCredenciales').addEventListener('click', async (ev) => {
        const btn = ev.target.closest('.accion-reset');
        if (!btn) return;
        if (!confirmar('Se generará una contraseña nueva para este usuario. ¿Continuar?')) return;
        try {
            const d = await Api.post('academias', 'resetear_clave', {
                academia_id: academiaCred,
                usuario_id: btn.dataset.id
            });
            mostrarClave(d.clave, 'Entrégala al usuario.');
        } catch (e) {
            aviso(e.message, 'danger');
        }
    });

    function mostrarClave(clave, nota = '') {
        const caja = document.getElementById('credencialesNueva');
        caja.classList.remove('d-none');
        document.getElementById('credencialTexto').textContent = nota ? `${nota} · ${clave}` : clave;
        document.getElementById('btnCopiarClave').onclick = () => {
            navigator.clipboard?.writeText(clave);
            aviso('Contraseña copiada.');
        };
    }

    buscar.addEventListener('input', esperar(() => { pagina = 1; listar(); }));
    filtro.addEventListener('change', () => { pagina = 1; listar(); });

    listar();
})();
