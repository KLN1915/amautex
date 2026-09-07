/* ============================================================
   Amautex · núcleo de JavaScript
   Api.get / Api.post hablan con index.php?p=modulo&accion=...
   y siempre reciben { ok, mensaje, datos }.
   ============================================================ */

const BASE = document.querySelector('meta[name="base-url"]')?.content || '';
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

const Api = {
    url(modulo, accion, params = {}) {
        const q = new URLSearchParams({ p: modulo, accion, ...params });
        return `${BASE}index.php?${q}`;
    },

    async get(modulo, accion, params = {}) {
        const r = await fetch(this.url(modulo, accion, params), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        return this.procesar(r);
    },

    async post(modulo, accion, datos = {}) {
        const cuerpo = datos instanceof FormData ? datos : new FormData();
        if (!(datos instanceof FormData)) {
            Object.entries(datos).forEach(([k, v]) => cuerpo.append(k, v));
        }
        cuerpo.append('_csrf', CSRF);

        const r = await fetch(this.url(modulo, accion), {
            method: 'POST',
            body: cuerpo,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF }
        });
        return this.procesar(r);
    },

    async procesar(r) {
        if (r.status === 401) {
            aviso('Tu sesión expiró. Vuelve a ingresar.', 'warning');
            setTimeout(() => location.href = `${BASE}index.php?p=login`, 1200);
            throw new Error('sesión expirada');
        }
        let json;
        try { json = await r.json(); }
        catch { throw new Error('Respuesta inválida del servidor.'); }
        if (!json.ok) throw new Error(json.mensaje || 'Error desconocido.');
        return json.datos;
    }
};

/** Notificación flotante. */
function aviso(mensaje, tipo = 'success') {
    const cont = document.getElementById('avisos');
    if (!cont) { alert(mensaje); return; }
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${tipo} border-0 show`;
    el.innerHTML = `<div class="d-flex">
        <div class="toast-body">${escapar(mensaje)}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
    cont.appendChild(el);
    setTimeout(() => el.remove(), 4000);
}

/** Escape de texto para inyectar en innerHTML. */
function escapar(t) {
    const d = document.createElement('div');
    d.textContent = t ?? '';
    return d.innerHTML;
}

/** Confirmación simple antes de acciones destructivas. */
function confirmar(mensaje) { return window.confirm(mensaje); }

/** Retrasa la ejecución mientras el usuario sigue escribiendo. */
function esperar(fn, ms = 350) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

// ---------- Interacciones del layout ----------
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnMenu')?.addEventListener('click', () => {
        document.getElementById('barraLateral')?.classList.toggle('oculta');
    });

    const tema = localStorage.getItem('tema');
    if (tema) document.documentElement.setAttribute('data-bs-theme', tema);

    document.getElementById('btnTema')?.addEventListener('click', () => {
        const actual = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', actual);
        localStorage.setItem('tema', actual);
    });

    // Selector de academia (solo lo renderiza el sidebar para el superadmin).
    // Cambia el contexto en la sesión y recarga la pantalla actual.
    document.getElementById('selectorAcademia')?.addEventListener('change', async (ev) => {
        const sel = ev.target;
        sel.disabled = true;
        try {
            await Api.post('academias', 'acceder', { id: sel.value });
            location.reload();
        } catch (e) {
            aviso(e.message, 'danger');
            sel.disabled = false;
        }
    });
});
