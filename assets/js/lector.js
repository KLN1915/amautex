/* Módulo Lector de fichas (superadmin): index.php?p=lector&accion=probar|guardar|quitar */
(() => {
    const form = document.getElementById('formLector');
    if (!form) return;
    const token = form.token, selPlantilla = form.plantilla;
    const estado = document.getElementById('estadoLector');
    const btnQuitar = document.getElementById('btnQuitar');

    document.getElementById('btnVerToken').addEventListener('click', () => {
        token.type = token.type === 'password' ? 'text' : 'password';
    });

    /** Llena el combo con las hojas que tiene el lector, conservando la elegida. */
    function pintarPlantillas(lista) {
        const actual = selPlantilla.value;
        selPlantilla.textContent = '';
        lista.forEach((p) => {
            const o = new Option(`${p.nombre} (${p.preguntas} preguntas, ${p.opciones} opciones)`, p.nombre);
            o.selected = p.nombre === actual;
            selPlantilla.add(o);
        });
    }

    function pintarEstado(e) {
        const campo = (n) => estado.querySelector(`[data-campo="${n}"]`);
        const badge = document.createElement('span');
        badge.className = 'badge ' + (e.configurado ? 'text-bg-success' : 'text-bg-secondary');
        badge.textContent = e.configurado ? 'configurado' : 'sin token';
        campo('situacion').replaceChildren(badge);
        campo('final').textContent = e.final ? '••••' + e.final : '—';
        campo('fuente').textContent = { sistema: 'guardado aquí (cifrado)', archivo: 'config.local.php', ninguna: '—' }[e.fuente];
        campo('plantilla').textContent = e.plantilla;
        campo('meta').textContent = e.meta ? `${e.meta.en} · ${e.meta.por ?? '—'}` : '—';
        btnQuitar.hidden = e.fuente !== 'sistema';
        token.value = '';
        token.type = 'password';
        token.placeholder = e.configurado ? `Déjalo vacío para conservar el actual (…${e.final})` : 'omr_…';
    }

    async function conBoton(btn, fn) {
        btn.disabled = true;
        try { await fn(); } catch (e) { aviso(e.message, 'danger'); } finally { btn.disabled = false; }
    }

    const btnProbar = document.getElementById('btnProbar');
    btnProbar.addEventListener('click', () => conBoton(btnProbar, async () => {
        const d = await Api.post('lector', 'probar', { url: form.url.value, token: token.value });
        pintarPlantillas(d.plantillas);
        aviso(`Conexión correcta: ${d.plantillas.length} hoja(s) en el lector.`);
    }));

    form.addEventListener('submit', (ev) => {
        ev.preventDefault();
        const btn = form.querySelector('button[type=submit]');
        conBoton(btn, async () => {
            const e = await Api.post('lector', 'guardar', new FormData(form));
            pintarEstado(e);
            aviso('Configuración guardada. Ya funciona para todas las academias.');
        });
    });

    btnQuitar.addEventListener('click', () => {
        if (!confirmar('¿Quitar el token? El escaneo de fichas dejará de funcionar en todas las academias.')) return;
        conBoton(btnQuitar, async () => {
            pintarEstado(await Api.post('lector', 'quitar'));
            aviso('Token eliminado.', 'warning');
        });
    });
})();
