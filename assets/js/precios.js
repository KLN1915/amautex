/* Módulo Precios: index.php?p=precios&accion=guardar */
(() => {
    const form = document.getElementById('formPrecios');
    if (!form) return;

    const cuerpo = document.getElementById('tablaResumenPrecios');
    const total  = document.getElementById('totalPrecios');

    const soles = (n) => 'S/ ' + Number(n).toFixed(2);

    /** Repinta el resumen con los montos que devolvió el servidor. */
    function pintarResumen(resumen) {
        cuerpo.querySelectorAll('tr').forEach((tr, i) => {
            const f = resumen.filas[i];
            if (!f) return;
            const celdas = tr.querySelectorAll('td');
            celdas[1].textContent = f.alumnos;
            celdas[2].textContent = soles(f.precio);
            celdas[3].textContent = soles(f.subtotal);
        });
        total.textContent = soles(resumen.total);
    }

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const btn = form.querySelector('button[type=submit]');
        if (!btn) return;
        btn.disabled = true;
        try {
            const d = await Api.post('precios', 'guardar', new FormData(form));
            pintarResumen(d.resumen);
            aviso('Precios actualizados.');
        } catch (e) {
            aviso(e.message, 'danger');
        } finally {
            btn.disabled = false;
        }
    });
})();
